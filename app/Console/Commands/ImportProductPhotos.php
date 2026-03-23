<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportProductPhotos extends Command
{
    protected $signature = 'products:import-photos
        {--dir= : Source directory (default: public/images/products)}
        {--match=name : Matching mode: name, code, or sequence}
        {--force : Overwrite existing product photo paths}
        {--dry-run : Show mapping without updating database}';

    protected $description = 'Import product photo paths from a folder into Oracle PRODUCTS photo column.';

    public function handle(): int
    {
        $sourceDir = trim((string) $this->option('dir'));
        if ($sourceDir === '') {
            $sourceDir = public_path('images/products');
        }
        $sourceDir = $this->normalizePath($sourceDir);

        if (! is_dir($sourceDir)) {
            $this->error("Directory not found: {$sourceDir}");

            return self::FAILURE;
        }

        $matchMode = mb_strtolower(trim((string) $this->option('match')));
        if (! in_array($matchMode, ['name', 'code', 'sequence'], true)) {
            $this->error('Invalid --match value. Use: name, code, or sequence.');

            return self::FAILURE;
        }

        [$photoColumn, $photoType] = $this->productPhotoColumn();
        if (! $photoColumn || ! $photoType) {
            $this->error('No supported product photo column found.');

            return self::FAILURE;
        }

        $files = $this->loadImageFiles($sourceDir);
        if ($files->isEmpty()) {
            $this->error("No image files found in: {$sourceDir}");

            return self::FAILURE;
        }

        $publicDir = $this->normalizePath(public_path());
        if (! str_starts_with($sourceDir.DIRECTORY_SEPARATOR, $publicDir.DIRECTORY_SEPARATOR)) {
            $this->error('Source directory must be inside public/ so image URLs work in the UI.');

            return self::FAILURE;
        }

        $products = $this->loadProducts($photoColumn, $photoType);
        if ($products->isEmpty()) {
            $this->error('No product records found.');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        $targets = $force
            ? $products->values()
            : $products->filter(static fn (object $row): bool => trim((string) ($row->photo_path ?? '')) === '')->values();

        if ($targets->isEmpty()) {
            $this->info('No products require photo updates (use --force to overwrite).');

            return self::SUCCESS;
        }

        $mapping = $this->buildMapping($targets, $files, $sourceDir, $publicDir, $matchMode);
        if ($mapping->isEmpty()) {
            $this->error('No product-photo matches were found with the selected mode.');

            return self::FAILURE;
        }

        $updated = 0;
        foreach ($mapping as $row) {
            $productNo = (string) $row['product_no'];
            $productName = (string) $row['product_name'];
            $photoPath = (string) $row['photo_path'];

            if ($dryRun) {
                $this->line("DRY RUN: {$productNo} {$productName} -> {$photoPath}");

                continue;
            }

            if ($photoType === 'BLOB') {
                DB::connection('oracle')->update(
                    'UPDATE PRODUCTS SET '.$photoColumn.' = TO_BLOB(UTL_RAW.CAST_TO_RAW(:photo_path)) WHERE PRODUCT_NO = :product_no',
                    [
                        'photo_path' => $photoPath,
                        'product_no' => $productNo,
                    ]
                );
            } elseif ($photoType === 'CLOB') {
                DB::connection('oracle')->update(
                    'UPDATE PRODUCTS SET '.$photoColumn.' = TO_CLOB(:photo_path) WHERE PRODUCT_NO = :product_no',
                    [
                        'photo_path' => $photoPath,
                        'product_no' => $productNo,
                    ]
                );
            } else {
                DB::connection('oracle')
                    ->table('PRODUCTS')
                    ->where('PRODUCT_NO', '=', $productNo)
                    ->update([$photoColumn => $photoPath]);
            }

            $updated++;
        }

        if ($dryRun) {
            $this->info('Dry run completed.');
        } else {
            $this->info("Updated {$updated} product photo(s).");
        }

        $unmatchedTargetCount = max(0, $targets->count() - $mapping->count());
        $unusedFileCount = max(0, $files->count() - $mapping->count());
        if ($unmatchedTargetCount > 0) {
            $this->warn("Unmatched products: {$unmatchedTargetCount}");
        }
        if ($unusedFileCount > 0) {
            $this->warn("Unused image files: {$unusedFileCount}");
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0:string|null,1:string|null}
     */
    private function productPhotoColumn(): array
    {
        $columns = DB::connection('oracle')
            ->table('USER_TAB_COLUMNS')
            ->selectRaw('COLUMN_NAME as column_name, DATA_TYPE as data_type')
            ->where('TABLE_NAME', '=', 'PRODUCTS')
            ->get()
            ->mapWithKeys(static function (object $row): array {
                $name = mb_strtoupper((string) ($row->column_name ?? $row->COLUMN_NAME ?? ''));
                $type = mb_strtoupper((string) ($row->data_type ?? $row->DATA_TYPE ?? ''));

                return $name !== '' ? [$name => $type] : [];
            });

        foreach (['PHOTO', 'IMAGE', 'PRODUCT_PHOTO', 'PRODUCT_IMAGE', 'PHOTO_PATH', 'IMAGE_PATH'] as $candidate) {
            $upper = mb_strtoupper($candidate);
            if ($columns->has($upper)) {
                return [$upper, (string) $columns->get($upper)];
            }
        }

        return [null, null];
    }

    /**
     * @return Collection<int, object>
     */
    private function loadProducts(string $photoColumn, string $photoType): Collection
    {
        $photoSelect = 'NULL as photo_path';
        if ($photoType === 'BLOB') {
            $photoSelect = 'CASE WHEN DBMS_LOB.GETLENGTH(p.'.$photoColumn.') <= 2000
                THEN UTL_RAW.CAST_TO_VARCHAR2(DBMS_LOB.SUBSTR(p.'.$photoColumn.', 2000, 1))
                ELSE NULL END as photo_path';
        } elseif ($photoType === 'CLOB') {
            $photoSelect = 'DBMS_LOB.SUBSTR(p.'.$photoColumn.', 4000, 1) as photo_path';
        } else {
            $photoSelect = 'p.'.$photoColumn.' as photo_path';
        }

        return DB::connection('oracle')
            ->table('PRODUCTS as p')
            ->selectRaw('p.PRODUCT_NO as product_no, p.PRODUCT_NAME as product_name, '.$photoSelect)
            ->orderBy('p.PRODUCT_NO')
            ->get();
    }

    /**
     * @return Collection<int, string>
     */
    private function loadImageFiles(string $sourceDir): Collection
    {
        $items = glob($sourceDir.DIRECTORY_SEPARATOR.'*') ?: [];
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

        return collect($items)
            ->filter(static function (string $path) use ($imageExts): bool {
                if (! is_file($path)) {
                    return false;
                }

                $ext = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));

                return in_array($ext, $imageExts, true);
            })
            ->sort(static fn (string $a, string $b): int => strnatcasecmp(basename($a), basename($b)))
            ->values();
    }

    /**
     * @param  Collection<int, object>  $products
     * @param  Collection<int, string>  $files
     * @return Collection<int, array{product_no:string, product_name:string, photo_path:string}>
     */
    private function buildMapping(Collection $products, Collection $files, string $sourceDir, string $publicDir, string $matchMode): Collection
    {
        if ($matchMode === 'code') {
            return $this->mapByProductCode($products, $files, $sourceDir, $publicDir);
        }
        if ($matchMode === 'sequence') {
            return $this->mapBySequence($products, $files, $sourceDir, $publicDir);
        }

        return $this->mapByProductName($products, $files, $sourceDir, $publicDir);
    }

    /**
     * @param  Collection<int, object>  $products
     * @param  Collection<int, string>  $files
     * @return Collection<int, array{product_no:string, product_name:string, photo_path:string}>
     */
    private function mapByProductCode(Collection $products, Collection $files, string $sourceDir, string $publicDir): Collection
    {
        $rows = collect();
        $fileIndex = [];

        foreach ($files as $file) {
            $base = mb_strtoupper(trim(pathinfo((string) $file, PATHINFO_FILENAME)));
            if ($base !== '' && ! isset($fileIndex[$base])) {
                $fileIndex[$base] = (string) $file;
            }
        }

        foreach ($products as $product) {
            $productNo = mb_strtoupper(trim((string) ($product->product_no ?? '')));
            $candidate = (string) ($fileIndex[$productNo] ?? '');
            if ($candidate === '') {
                continue;
            }

            $rows->push([
                'product_no' => (string) ($product->product_no ?? ''),
                'product_name' => (string) ($product->product_name ?? ''),
                'photo_path' => $this->toWebPath($candidate, $sourceDir, $publicDir),
            ]);
        }

        return $rows;
    }

    /**
     * @param  Collection<int, object>  $products
     * @param  Collection<int, string>  $files
     * @return Collection<int, array{product_no:string, product_name:string, photo_path:string}>
     */
    private function mapBySequence(Collection $products, Collection $files, string $sourceDir, string $publicDir): Collection
    {
        $count = min($products->count(), $files->count());
        $rows = collect();

        for ($i = 0; $i < $count; $i++) {
            $product = $products[$i];
            $file = (string) $files[$i];
            $rows->push([
                'product_no' => (string) ($product->product_no ?? ''),
                'product_name' => (string) ($product->product_name ?? ''),
                'photo_path' => $this->toWebPath($file, $sourceDir, $publicDir),
            ]);
        }

        return $rows;
    }

    /**
     * @param  Collection<int, object>  $products
     * @param  Collection<int, string>  $files
     * @return Collection<int, array{product_no:string, product_name:string, photo_path:string}>
     */
    private function mapByProductName(Collection $products, Collection $files, string $sourceDir, string $publicDir): Collection
    {
        $rows = collect();
        $fileIndex = $this->indexFilesByNormalizedName($files);

        foreach ($products as $product) {
            $nameKey = $this->normalizeToken((string) ($product->product_name ?? ''));
            if ($nameKey === '') {
                continue;
            }

            $candidate = (string) ($fileIndex[$nameKey] ?? '');
            if ($candidate === '') {
                continue;
            }

            $rows->push([
                'product_no' => (string) ($product->product_no ?? ''),
                'product_name' => (string) ($product->product_name ?? ''),
                'photo_path' => $this->toWebPath($candidate, $sourceDir, $publicDir),
            ]);
        }

        return $rows;
    }

    /**
     * @param  Collection<int, string>  $files
     * @return array<string, string>
     */
    private function indexFilesByNormalizedName(Collection $files): array
    {
        $indexed = [];
        $ranked = $files->sortByDesc(static fn (string $path): int => (int) (filemtime($path) ?: 0))->values();

        foreach ($ranked as $file) {
            $key = $this->normalizeToken(pathinfo((string) $file, PATHINFO_FILENAME));
            if ($key === '' || isset($indexed[$key])) {
                continue;
            }
            $indexed[$key] = (string) $file;
        }

        return $indexed;
    }

    private function toWebPath(string $filePath, string $sourceDir, string $publicDir): string
    {
        $dirRelative = trim(substr($sourceDir, mb_strlen($publicDir)), '\\/');
        $dirRelative = str_replace('\\', '/', $dirRelative);
        $filename = basename($filePath);

        return trim($dirRelative.'/'.$filename, '/');
    }

    private function normalizeToken(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/i', '', $normalized) ?? '';

        return $normalized;
    }

    private function normalizePath(string $path): string
    {
        $real = realpath($path);

        return $real !== false ? $real : $path;
    }
}

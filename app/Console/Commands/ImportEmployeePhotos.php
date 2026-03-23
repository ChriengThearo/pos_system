<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportEmployeePhotos extends Command
{
    protected $signature = 'employees:import-photos
        {--dir= : Source directory (default: public/images/employees)}
        {--match=sequence : Matching mode: sequence, id, or name}
        {--force : Overwrite existing employee photo paths}
        {--dry-run : Show mapping without updating database}';

    protected $description = 'Import employee photo paths from a folder into Oracle EMPLOYEES.PHOTO.';

    public function handle(): int
    {
        $sourceDir = trim((string) $this->option('dir'));
        if ($sourceDir === '') {
            $sourceDir = public_path('images/employees');
        }
        $sourceDir = $this->normalizePath($sourceDir);

        if (! is_dir($sourceDir)) {
            $this->error("Directory not found: {$sourceDir}");

            return self::FAILURE;
        }

        $matchMode = mb_strtolower(trim((string) $this->option('match')));
        if (! in_array($matchMode, ['sequence', 'id', 'name'], true)) {
            $this->error('Invalid --match value. Use: sequence, id, or name.');

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

        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        $employees = DB::connection('oracle')
            ->table('EMPLOYEES')
            ->selectRaw('
                EMPLOYEE_ID as employee_id,
                EMPLOYEE_NAME as employee_name,
                UTL_RAW.CAST_TO_VARCHAR2(DBMS_LOB.SUBSTR(PHOTO, 4000, 1)) as photo_path
            ')
            ->orderBy('EMPLOYEE_ID')
            ->get();

        if ($employees->isEmpty()) {
            $this->error('No employee records found.');

            return self::FAILURE;
        }

        $targets = $force
            ? $employees->values()
            : $employees->filter(static fn (object $row): bool => trim((string) ($row->photo_path ?? '')) === '')->values();

        if ($targets->isEmpty()) {
            $this->info('No employees require photo updates (use --force to overwrite).');

            return self::SUCCESS;
        }

        $mapping = $this->buildMapping($targets, $files, $sourceDir, $publicDir, $matchMode);
        if ($mapping->isEmpty()) {
            $this->error('No employee-photo matches were found with the selected mode.');

            return self::FAILURE;
        }

        $updated = 0;
        foreach ($mapping as $row) {
            $employeeId = (int) $row['employee_id'];
            $photoPath = (string) $row['photo_path'];
            $employeeName = (string) $row['employee_name'];

            if ($dryRun) {
                $this->line("DRY RUN: #{$employeeId} {$employeeName} -> {$photoPath}");

                continue;
            }

            DB::connection('oracle')->update(
                'UPDATE EMPLOYEES
                 SET PHOTO = TO_BLOB(UTL_RAW.CAST_TO_RAW(:photo_path))
                 WHERE EMPLOYEE_ID = :employee_id',
                [
                    'photo_path' => $photoPath,
                    'employee_id' => $employeeId,
                ]
            );

            $updated++;
        }

        if ($dryRun) {
            $this->info('Dry run completed.');
        } else {
            $this->info("Updated {$updated} employee photo(s).");
        }

        $unmatchedTargetCount = max(0, $targets->count() - $mapping->count());
        $unusedFileCount = max(0, $files->count() - $mapping->count());
        if ($unmatchedTargetCount > 0) {
            $this->warn("Unmatched employees: {$unmatchedTargetCount}");
        }
        if ($unusedFileCount > 0) {
            $this->warn("Unused image files: {$unusedFileCount}");
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, string>
     */
    private function loadImageFiles(string $sourceDir): Collection
    {
        $items = glob($sourceDir.DIRECTORY_SEPARATOR.'*') ?: [];
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

        $files = collect($items)
            ->filter(static function (string $path) use ($imageExts): bool {
                if (! is_file($path)) {
                    return false;
                }

                $ext = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));

                return in_array($ext, $imageExts, true);
            })
            ->sort(static fn (string $a, string $b): int => strnatcasecmp(basename($a), basename($b)))
            ->values();

        return $files;
    }

    /**
     * @param  Collection<int, object>  $employees
     * @param  Collection<int, string>  $files
     * @return Collection<int, array{employee_id:int, employee_name:string, photo_path:string}>
     */
    private function buildMapping(Collection $employees, Collection $files, string $sourceDir, string $publicDir, string $matchMode): Collection
    {
        if ($matchMode === 'sequence') {
            return $this->mapBySequence($employees, $files, $sourceDir, $publicDir);
        }

        if ($matchMode === 'id') {
            return $this->mapByEmployeeId($employees, $files, $sourceDir, $publicDir);
        }

        return $this->mapByEmployeeName($employees, $files, $sourceDir, $publicDir);
    }

    /**
     * @param  Collection<int, object>  $employees
     * @param  Collection<int, string>  $files
     * @return Collection<int, array{employee_id:int, employee_name:string, photo_path:string}>
     */
    private function mapBySequence(Collection $employees, Collection $files, string $sourceDir, string $publicDir): Collection
    {
        $count = min($employees->count(), $files->count());
        $rows = collect();

        for ($i = 0; $i < $count; $i++) {
            $employee = $employees[$i];
            $file = (string) $files[$i];
            $rows->push([
                'employee_id' => (int) $employee->employee_id,
                'employee_name' => (string) ($employee->employee_name ?? ''),
                'photo_path' => $this->toWebPath($file, $sourceDir, $publicDir),
            ]);
        }

        return $rows;
    }

    /**
     * @param  Collection<int, object>  $employees
     * @param  Collection<int, string>  $files
     * @return Collection<int, array{employee_id:int, employee_name:string, photo_path:string}>
     */
    private function mapByEmployeeId(Collection $employees, Collection $files, string $sourceDir, string $publicDir): Collection
    {
        $rows = collect();
        $fileIndex = [];

        foreach ($files as $file) {
            $base = mb_strtolower(pathinfo((string) $file, PATHINFO_FILENAME));
            $fileIndex[$base] = (string) $file;
        }

        foreach ($employees as $employee) {
            $id = (int) $employee->employee_id;
            $candidate = (string) ($fileIndex[(string) $id] ?? '');
            if ($candidate === '') {
                $candidate = (string) ($fileIndex['employee_'.$id] ?? '');
            }

            if ($candidate === '') {
                continue;
            }

            $rows->push([
                'employee_id' => $id,
                'employee_name' => (string) ($employee->employee_name ?? ''),
                'photo_path' => $this->toWebPath($candidate, $sourceDir, $publicDir),
            ]);
        }

        return $rows;
    }

    /**
     * @param  Collection<int, object>  $employees
     * @param  Collection<int, string>  $files
     * @return Collection<int, array{employee_id:int, employee_name:string, photo_path:string}>
     */
    private function mapByEmployeeName(Collection $employees, Collection $files, string $sourceDir, string $publicDir): Collection
    {
        $rows = collect();
        $fileIndex = [];

        foreach ($files as $file) {
            $normalized = $this->normalizeToken(pathinfo((string) $file, PATHINFO_FILENAME));
            if ($normalized !== '' && ! isset($fileIndex[$normalized])) {
                $fileIndex[$normalized] = (string) $file;
            }
        }

        foreach ($employees as $employee) {
            $nameKey = $this->normalizeToken((string) ($employee->employee_name ?? ''));
            $candidate = (string) ($fileIndex[$nameKey] ?? '');
            if ($candidate === '') {
                continue;
            }

            $rows->push([
                'employee_id' => (int) $employee->employee_id,
                'employee_name' => (string) ($employee->employee_name ?? ''),
                'photo_path' => $this->toWebPath($candidate, $sourceDir, $publicDir),
            ]);
        }

        return $rows;
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

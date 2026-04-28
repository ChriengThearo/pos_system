<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CjDropshippingService
{
    /**
     * @return array{
     *   source:string,
     *   products:array<int,array{name:string,image:string,cost_price:float}>,
     *   meta:array{
     *     fetched:int,
     *     total_available:int|null,
     *     current_page:int,
     *     page_size:int,
     *     has_more:bool,
     *     next_page:int|null
     *   }
     * }
     */
    public function fetchProducts(string $query = '', int $page = 1, int $perPage = 10): array
    {
        $keyword = trim($query);
        $pageNumber = $this->sanitizePageNumber($page);
        $pageSize = $this->sanitizePageSize($perPage);
        $remote = $this->fetchFromApi($keyword, $pageNumber, $pageSize);

        if ($remote !== null) {
            return [
                'source' => 'cj',
                'products' => $remote['products'],
                'meta' => $remote['meta'],
            ];
        }

        $mockProducts = $this->paginateLocal($this->filterByKeyword($this->mockProducts(), $keyword), $pageNumber, $pageSize);

        return [
            'source' => 'mock',
            'products' => $mockProducts['products'],
            'meta' => $mockProducts['meta'],
        ];
    }

    /**
     * @return array{
     *   products:array<int,array{name:string,image:string,cost_price:float}>,
     *   meta:array{
     *     fetched:int,
     *     total_available:int|null,
     *     current_page:int,
     *     page_size:int,
     *     has_more:bool,
     *     next_page:int|null
     *   }
     * }|null
     */
    private function fetchFromApi(string $keyword, int $page, int $perPage): ?array
    {
        $baseUrl = rtrim((string) config('services.cj_dropshipping.base_url', ''), '/');
        $token = trim((string) config('services.cj_dropshipping.api_token', ''));
        $endpoint = '/'.ltrim((string) config('services.cj_dropshipping.product_endpoint', '/api2.0/v1/product/list'), '/');
        $tokenHeader = trim((string) config('services.cj_dropshipping.token_header', 'CJ-Access-Token'));
        $queryKey = trim((string) config('services.cj_dropshipping.query_key', 'keywords'));
        $timeout = max(3, (int) config('services.cj_dropshipping.timeout', 15));
        $pageSize = $this->sanitizePageSize($perPage > 0 ? $perPage : (int) config('services.cj_dropshipping.page_size', 10));
        $cacheTtl = max(0, (int) config('services.cj_dropshipping.cache_ttl', 120));

        if ($baseUrl === '' || $token === '') {
            return null;
        }

        $cacheKey = $this->cacheKey($baseUrl, $endpoint, $keyword, $page, $pageSize);
        if ($cacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $query = [
                'pageNum' => $page,
                'pageSize' => $pageSize,
            ];
            if ($keyword !== '') {
                $query[$queryKey] = $keyword;
            }

            $response = Http::timeout($timeout)
                ->connectTimeout(min(5, $timeout))
                ->acceptJson()
                ->withHeaders([
                    $tokenHeader => $token,
                    'Authorization' => 'Bearer '.$token,
                ])
                ->get($baseUrl.$endpoint, $query);

            if (! $response->ok()) {
                Log::warning('CJ API request failed.', [
                    'status' => $response->status(),
                    'endpoint' => $baseUrl.$endpoint,
                    'page' => $page,
                ]);

                return null;
            }

            $json = $response->json();
            if (! is_array($json)) {
                return null;
            }

            $data = is_array($json['data'] ?? null) ? $json['data'] : [];
            $totalRaw = $data['total'] ?? null;
            $totalAvailable = is_numeric($totalRaw) ? (int) $totalRaw : null;

            $products = [];
            foreach ($this->extractProductItems($json) as $item) {
                $normalized = $this->normalizeProduct($item);
                if ($normalized !== null) {
                    $products[] = $normalized;
                }
            }

            $fetched = count($products);
            $hasMore = $totalAvailable !== null
                ? ($page * $pageSize) < $totalAvailable
                : $fetched === $pageSize;

            $result = [
                'products' => $products,
                'meta' => [
                    'fetched' => $fetched,
                    'total_available' => $totalAvailable,
                    'current_page' => $page,
                    'page_size' => $pageSize,
                    'has_more' => $hasMore,
                    'next_page' => $hasMore ? ($page + 1) : null,
                ],
            ];

            if ($cacheTtl > 0) {
                Cache::put($cacheKey, $result, now()->addSeconds($cacheTtl));
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('CJ API request threw an exception.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<int,mixed>
     */
    private function extractProductItems(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $candidates = [
            $payload['data']['list'] ?? null,
            $payload['data']['products'] ?? null,
            $payload['data']['data'] ?? null,
            $payload['products'] ?? null,
            $payload['list'] ?? null,
            $payload,
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && array_is_list($candidate)) {
                return $candidate;
            }
        }

        return [];
    }

    /**
     * @return array{name:string,image:string,cost_price:float}|null
     */
    private function normalizeProduct(mixed $item): ?array
    {
        if (! is_array($item)) {
            return null;
        }

        $name = trim((string) (
            $item['nameEn']
            ?? $item['productNameEn']
            ?? $item['name']
            ?? $item['productName']
            ?? $item['product_name']
            ?? $item['title']
            ?? $item['productSku']
            ?? ''
        ));

        $image = $item['image']
            ?? $item['productImage']
            ?? $item['imageUrl']
            ?? $item['image_url']
            ?? $item['mainImage']
            ?? '';
        if (is_array($image)) {
            $image = (string) ($image[0] ?? '');
        }
        $image = trim((string) $image);

        $cost = $item['cost_price']
            ?? $item['costPrice']
            ?? $item['cost']
            ?? $item['price']
            ?? $item['sellPrice']
            ?? $item['sell_price']
            ?? null;
        $costPrice = $this->parseCostPrice($cost);

        if ($name === '' || $image === '' || $costPrice === null) {
            return null;
        }

        return [
            'name' => $name,
            'image' => $image,
            'cost_price' => round($costPrice, 2),
        ];
    }

    private function parseCostPrice(mixed $cost): ?float
    {
        if (is_numeric($cost)) {
            return (float) $cost;
        }

        if (! is_string($cost)) {
            return null;
        }

        $value = trim($cost);
        if ($value === '') {
            return null;
        }

        $matches = [];
        preg_match_all('/\d+(?:\.\d+)?/', $value, $matches);
        $numbers = array_map('floatval', $matches[0] ?? []);
        if ($numbers === []) {
            return null;
        }

        return min($numbers);
    }

    /**
     * @param  array<int,array{name:string,image:string,cost_price:float}>  $products
     * @return array<int,array{name:string,image:string,cost_price:float}>
     */
    private function filterByKeyword(array $products, string $keyword): array
    {
        if ($keyword === '') {
            return array_values($products);
        }

        $needle = mb_strtolower($keyword);

        return array_values(array_filter(
            $products,
            static fn (array $product): bool => str_contains(mb_strtolower($product['name']), $needle)
        ));
    }

    /**
     * @return array<int,array{name:string,image:string,cost_price:float}>
     */
    private function mockProducts(): array
    {
        return [
            [
                'name' => 'Sample Product',
                'image' => 'https://via.placeholder.com/150',
                'cost_price' => 5.00,
            ],
            [
                'name' => 'Bluetooth Earbuds TWS',
                'image' => 'https://via.placeholder.com/150?text=Earbuds',
                'cost_price' => 8.20,
            ],
            [
                'name' => 'Portable USB Fan',
                'image' => 'https://via.placeholder.com/150?text=USB+Fan',
                'cost_price' => 3.75,
            ],
            [
                'name' => 'Mini LED Desk Lamp',
                'image' => 'https://via.placeholder.com/150?text=Desk+Lamp',
                'cost_price' => 4.60,
            ],
        ];
    }

    /**
     * @param  array<int,array{name:string,image:string,cost_price:float}>  $products
     * @return array{
     *   products:array<int,array{name:string,image:string,cost_price:float}>,
     *   meta:array{
     *     fetched:int,
     *     total_available:int|null,
     *     current_page:int,
     *     page_size:int,
     *     has_more:bool,
     *     next_page:int|null
     *   }
     * }
     */
    private function paginateLocal(array $products, int $page, int $pageSize): array
    {
        $total = count($products);
        $offset = ($page - 1) * $pageSize;
        $chunk = array_slice($products, $offset, $pageSize);
        $fetched = count($chunk);
        $hasMore = ($offset + $fetched) < $total;

        return [
            'products' => array_values($chunk),
            'meta' => [
                'fetched' => $fetched,
                'total_available' => $total,
                'current_page' => $page,
                'page_size' => $pageSize,
                'has_more' => $hasMore,
                'next_page' => $hasMore ? ($page + 1) : null,
            ],
        ];
    }

    private function sanitizePageNumber(int $page): int
    {
        return max(1, $page);
    }

    private function sanitizePageSize(int $pageSize): int
    {
        if ($pageSize < 1) {
            return 1;
        }

        return min($pageSize, 100);
    }

    private function cacheKey(string $baseUrl, string $endpoint, string $keyword, int $page, int $pageSize): string
    {
        $fingerprint = implode('|', [
            $baseUrl,
            $endpoint,
            mb_strtolower($keyword),
            (string) $page,
            (string) $pageSize,
        ]);

        return 'china_store:cj_products:'.sha1($fingerprint);
    }
}

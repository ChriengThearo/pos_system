<?php

namespace App\Http\Controllers;

use App\Support\CjDropshippingService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChinaStoreController extends Controller
{
    public function index(): View
    {
        return view('china-store.index');
    }

    public function products(Request $request, CjDropshippingService $cjDropshipping): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $defaultPageSize = (int) Config::get('services.cj_dropshipping.page_size', 10);
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', $defaultPageSize);
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));

        $data = $cjDropshipping->fetchProducts($q, $page, $perPage);

        return response()->json($data);
    }

    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'image' => ['required', 'string', 'max:1900'],
            'cost_price' => ['required', 'numeric', 'min:0'],
        ]);

        $originalName = trim((string) $validated['name']);
        $productName = mb_substr($originalName, 0, 40);
        $image = trim((string) $validated['image']);
        $costPrice = round((float) $validated['cost_price'], 2);
        $sellPrice = round($costPrice * 1.5, 2);
        $profitPercent = 50.00;

        if ($productName === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Product name is required.',
            ], 422);
        }

        if ($this->productNameExists($productName)) {
            return response()->json([
                'status' => 'skipped',
                'message' => 'Product already exists. Import skipped.',
                'product_name' => $productName,
            ]);
        }

        try {
            $productNo = DB::connection('oracle')->transaction(function () use (
                $productName,
                $image,
                $costPrice,
                $sellPrice,
                $profitPercent
            ): string {
                $productNo = $this->nextProductCode();

                DB::connection('oracle')->insert(
                    'INSERT INTO PRODUCTS (PRODUCT_NO, PRODUCT_NAME, PRODUCT_TYPE, SELL_PRICE, COST_PRICE, PROFIT_PERCENT, UNIT_MEASURE, QTY_ON_HAND, STATUS)
                     VALUES (:product_no, :product_name, :product_type, :sell_price, :cost_price, :profit_percent, :unit_measure, :qty_on_hand, :status)',
                    [
                        'product_no' => $productNo,
                        'product_name' => $productName,
                        'product_type' => null,
                        'sell_price' => $sellPrice,
                        'cost_price' => $costPrice,
                        'profit_percent' => $profitPercent,
                        'unit_measure' => null,
                        'qty_on_hand' => 0,
                        'status' => null,
                    ]
                );

                DB::connection('oracle')->statement(
                    'INSERT INTO PRODUCT_PHOTO (PRODUCT_ID, MEDIA, CREATED_AT, UPDATED_AT)
                     VALUES (:product_id, TO_BLOB(UTL_RAW.CAST_TO_RAW(:media)), SYSTIMESTAMP, SYSTIMESTAMP)',
                    [
                        'product_id' => $productNo,
                        'media' => $image,
                    ]
                );

                return $productNo;
            }, 3);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001') && $this->productNameExists($productName)) {
                return response()->json([
                    'status' => 'skipped',
                    'message' => 'Product already exists. Import skipped.',
                    'product_name' => $productName,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to import product.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product imported to stock.',
            'data' => [
                'product_no' => $productNo,
                'name' => $productName,
                'cost_price' => $costPrice,
                'selling_price' => $sellPrice,
                'profit_percent' => $profitPercent,
                'stock' => 0,
            ],
        ]);
    }

    private function productNameExists(string $productName): bool
    {
        return DB::connection('oracle')
            ->table('PRODUCTS')
            ->whereRaw('UPPER(PRODUCT_NAME) = ?', [mb_strtoupper($productName)])
            ->exists();
    }

    private function nextProductCode(): string
    {
        $lastCode = DB::connection('oracle')
            ->table('PRODUCTS')
            ->selectRaw('MAX(PRODUCT_NO) as last_code')
            ->value('last_code');

        if ($lastCode === null) {
            return 'P0001';
        }

        $digits = preg_replace('/\D/', '', (string) $lastCode);
        $digits = $digits === null ? '' : $digits;

        if ($digits === '') {
            return 'P0001';
        }

        $number = (int) $digits;
        $length = max(4, strlen($digits));

        return 'P'.str_pad((string) ($number + 1), $length, '0', STR_PAD_LEFT);
    }
}

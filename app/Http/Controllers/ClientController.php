<?php

namespace App\Http\Controllers;

use App\Support\StaffAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $conn = DB::connection('oracle');
        $q = trim((string) $request->query('q', ''));
        $typeRaw = trim((string) $request->query('type', ''));
        $typeId = $typeRaw !== '' && ctype_digit($typeRaw) ? (int) $typeRaw : null;

        $clientsQuery = $conn->table('CLIENTS as c')
            ->leftJoin('CLIENT_TYPE as t', 't.CLIENTTYPE_ID', '=', 'c.CLIENT_TYPE')
            ->selectRaw('
                c.CLIENT_NO as client_no,
                c.CLIENT_NAME as client_name,
                c.PHONE as phone,
                c.ADDRESS as address,
                c.CITY as city,
                c.CLIENT_TYPE as client_type,
                c.DISCOUNT as discount,
                t.TYPE_NAME as type_name,
                t.DISCOUNT_RATE as type_discount
            ');

        $this->applyClientSearch($clientsQuery, $q);
        if ($typeId !== null) {
            $clientsQuery->where('c.CLIENT_TYPE', '=', $typeId);
        }

        $clients = $clientsQuery
            ->orderBy('c.CLIENT_NAME')
            ->paginate(15)
            ->appends($request->query());

        $clientCount = (int) $conn->table('CLIENTS')->count();
        $clientTypes = $conn->table('CLIENT_TYPE')
            ->selectRaw('CLIENTTYPE_ID as clienttype_id, TYPE_NAME as type_name, DISCOUNT_RATE as discount_rate')
            ->orderBy('CLIENTTYPE_ID')
            ->get();
        $typeClientCounts = $conn->table('CLIENTS')
            ->selectRaw('CLIENT_TYPE as client_type, COUNT(*) as total')
            ->groupBy('CLIENT_TYPE')
            ->get()
            ->mapWithKeys(static function (object $row): array {
                return [
                    (string) ($row->client_type ?? '') => (int) ($row->total ?? 0),
                ];
            });
        $typeCount = (int) $clientTypes->count();
        $avgDiscountPercent = (float) ($conn->table('CLIENTS')
            ->selectRaw('
                NVL(AVG(
                    CASE
                        WHEN NVL(DISCOUNT, 0) <= 0 THEN 0
                        WHEN DISCOUNT >= 10 THEN DISCOUNT
                        WHEN DISCOUNT >= 1 THEN DISCOUNT * 10
                        ELSE DISCOUNT * 100
                    END
                ), 0) as avg_discount_percent
            ')
            ->value('avg_discount_percent') ?? 0);
        $clientTypesPayload = $clientTypes->map(function ($type) {
            return [
                'id' => (int) ($type->clienttype_id ?? $type->CLIENTTYPE_ID ?? 0),
                'name' => (string) ($type->type_name ?? $type->TYPE_NAME ?? ''),
                'discount' => (float) ($type->discount_rate ?? $type->DISCOUNT_RATE ?? 0),
            ];
        })->values();

        return view('clients.index', [
            'clients' => $clients,
            'clientTypes' => $clientTypes,
            'clientTypesPayload' => $clientTypesPayload,
            'typeClientCounts' => $typeClientCounts,
            'q' => $q,
            'selectedType' => $typeId,
            'metrics' => [
                'clients' => $clientCount,
                'types' => $typeCount,
                'avg_discount_percent' => $avgDiscountPercent,
            ],
            'canManageClients' => StaffAuth::can('clients.manage'),
            'canManageTypes' => StaffAuth::can('client-types.manage'),
        ]);
    }

    public function data(Request $request)
    {
        $conn = DB::connection('oracle');
        $q = trim((string) $request->query('q', ''));
        $typeRaw = trim((string) $request->query('type', ''));
        $typeId = $typeRaw !== '' && ctype_digit($typeRaw) ? (int) $typeRaw : null;

        $clientsQuery = $conn->table('CLIENTS as c')
            ->leftJoin('CLIENT_TYPE as t', 't.CLIENTTYPE_ID', '=', 'c.CLIENT_TYPE')
            ->selectRaw('
                c.CLIENT_NO as client_no,
                c.CLIENT_NAME as client_name,
                c.PHONE as phone,
                c.ADDRESS as address,
                c.CITY as city,
                c.CLIENT_TYPE as client_type,
                c.DISCOUNT as discount,
                t.TYPE_NAME as type_name,
                t.DISCOUNT_RATE as type_discount
            ');

        $this->applyClientSearch($clientsQuery, $q);
        if ($typeId !== null) {
            $clientsQuery->where('c.CLIENT_TYPE', '=', $typeId);
        }

        $clients = $clientsQuery
            ->orderBy('c.CLIENT_NAME')
            ->limit(50)
            ->get();

        return response()->json([
            'q' => $q,
            'count' => $clients->count(),
            'clients' => $clients->values(),
        ]);
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    private function applyClientSearch($query, string $q): void
    {
        if ($q === '') {
            return;
        }

        $keyword = '%'.mb_strtoupper($q).'%';
        $numericKeyword = mb_strtoupper(trim((string) preg_replace('/\s+/', '', str_replace(['%', ','], ['', '.'], $q))));
        $numericLike = '%'.$numericKeyword.'%';
        $isNumericKeyword = $numericKeyword !== '' && is_numeric($numericKeyword);
        $numericValue = $isNumericKeyword ? (float) $numericKeyword : null;
        $discountPercentExpr = 'CASE
            WHEN NVL(c.DISCOUNT, 0) <= 0 THEN 0
            WHEN c.DISCOUNT >= 10 THEN c.DISCOUNT
            WHEN c.DISCOUNT >= 1 THEN c.DISCOUNT * 10
            ELSE c.DISCOUNT * 100
        END';
        $typeDiscountPercentExpr = 'CASE
            WHEN NVL(t.DISCOUNT_RATE, 0) <= 0 THEN 0
            WHEN t.DISCOUNT_RATE >= 10 THEN t.DISCOUNT_RATE
            WHEN t.DISCOUNT_RATE >= 1 THEN t.DISCOUNT_RATE * 10
            ELSE t.DISCOUNT_RATE * 100
        END';

        $query->where(function ($sub) use (
            $keyword,
            $numericLike,
            $isNumericKeyword,
            $numericValue,
            $discountPercentExpr,
            $typeDiscountPercentExpr
        ): void {
            $sub->whereRaw('UPPER(NVL(c.CLIENT_NAME, \'\')) LIKE ?', [$keyword])
                ->orWhereRaw('UPPER(NVL(c.PHONE, \'\')) LIKE ?', [$keyword])
                ->orWhereRaw('UPPER(NVL(c.ADDRESS, \'\')) LIKE ?', [$keyword])
                ->orWhereRaw('UPPER(NVL(c.CITY, \'\')) LIKE ?', [$keyword])
                ->orWhereRaw('UPPER(NVL(t.TYPE_NAME, \'\')) LIKE ?', [$keyword])
                ->orWhereRaw('UPPER(TO_CHAR(NVL(c.CLIENT_NO, 0))) LIKE ?', [$keyword])
                ->orWhereRaw('UPPER(TO_CHAR(NVL(c.CLIENT_TYPE, 0))) LIKE ?', [$keyword])
                ->orWhereRaw('UPPER(TO_CHAR(NVL(c.DISCOUNT, 0))) LIKE ?', [$keyword])
                ->orWhereRaw('UPPER(TO_CHAR(NVL(t.DISCOUNT_RATE, 0))) LIKE ?', [$keyword])
                ->orWhereRaw("UPPER(TO_CHAR({$discountPercentExpr}, 'FM9999990D00', 'NLS_NUMERIC_CHARACTERS=''.,''')) LIKE ?", [$numericLike])
                ->orWhereRaw("UPPER(TO_CHAR({$typeDiscountPercentExpr}, 'FM9999990D00', 'NLS_NUMERIC_CHARACTERS=''.,''')) LIKE ?", [$numericLike]);

            if ($isNumericKeyword && $numericValue !== null) {
                $sub->orWhereRaw("ABS({$discountPercentExpr} - ?) < 0.0001", [$numericValue])
                    ->orWhereRaw("ABS({$typeDiscountPercentExpr} - ?) < 0.0001", [$numericValue])
                    ->orWhereRaw('ABS(NVL(c.DISCOUNT, 0) - ?) < 0.0001', [$numericValue])
                    ->orWhereRaw('ABS(NVL(t.DISCOUNT_RATE, 0) - ?) < 0.0001', [$numericValue]);
            }
        });
    }

    public function dss(Request $request): JsonResponse
    {
        $conn = DB::connection('oracle');
        $type = trim((string) $request->query('type', 'summary'));

        // ── Spending history with date range filter ─────────────────────────
        if ($type === 'history') {
            $range  = trim((string) $request->query('range', '30'));
            $from   = trim((string) $request->query('from', ''));
            $to     = trim((string) $request->query('to', ''));
            $client = trim((string) $request->query('client_no', ''));

            $query = $conn->table('INVOICES as i')
                ->join('CLIENTS as c', 'c.CLIENT_NO', '=', 'i.CLIENT_NO')
                ->leftJoin('INVOICE_DETAILS as d', 'd.INVOICE_NO', '=', 'i.INVOICE_NO')
                ->leftJoin('PRODUCTS as p', 'p.PRODUCT_NO', '=', 'd.PRODUCT_NO')
                ->selectRaw("
                    TRUNC(i.INVOICE_DATE) as period_date,
                    COUNT(DISTINCT i.INVOICE_NO) as order_count,
                    NVL(SUM(d.QTY * NVL(d.PRICE, p.SELL_PRICE)), 0) as total_spending
                ");

            if ($client !== '') {
                $query->where('c.CLIENT_NO', '=', $client);
            }

            // Use positional ? placeholders — Oracle PDO handles these reliably
            if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
                $query->whereRaw("TRUNC(i.INVOICE_DATE) >= TO_DATE(?, 'YYYY-MM-DD')", [$from]);
            } elseif (in_array($range, ['7', '30', '90'], true)) {
                $query->whereRaw('TRUNC(i.INVOICE_DATE) >= TRUNC(SYSDATE) - ?', [(int) $range]);
            } elseif ($range === '365') {
                $query->whereRaw("TRUNC(i.INVOICE_DATE) >= ADD_MONTHS(TRUNC(SYSDATE), -12)");
            }

            if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                $query->whereRaw("TRUNC(i.INVOICE_DATE) <= TO_DATE(?, 'YYYY-MM-DD')", [$to]);
            }

            $rows = $query
                ->groupByRaw('TRUNC(i.INVOICE_DATE)')
                ->orderByRaw('TRUNC(i.INVOICE_DATE) ASC')
                ->get()
                ->map(static function (object $row): array {
                    return [
                        'date'           => $row->period_date
                            ? \Illuminate\Support\Carbon::parse((string) $row->period_date)->format('Y-m-d')
                            : '',
                        'order_count'    => (int) ($row->order_count ?? 0),
                        'total_spending' => round((float) ($row->total_spending ?? 0), 2),
                    ];
                });

            return response()->json(['history' => $rows]);
        }

        // ── Top 10 clients by total spending ────────────────────────────────
        if ($type === 'top_clients') {
            $cacheKey = 'dss_top_clients_v1';
            $rows = Cache::remember($cacheKey, 300, static function () use ($conn): array {
                return $conn->table('INVOICES as i')
                    ->join('CLIENTS as c', 'c.CLIENT_NO', '=', 'i.CLIENT_NO')
                    ->leftJoin('INVOICE_DETAILS as d', 'd.INVOICE_NO', '=', 'i.INVOICE_NO')
                    ->leftJoin('PRODUCTS as p', 'p.PRODUCT_NO', '=', 'd.PRODUCT_NO')
                    ->selectRaw('
                        c.CLIENT_NO as client_no,
                        c.CLIENT_NAME as client_name,
                        COUNT(DISTINCT i.INVOICE_NO) as order_count,
                        NVL(SUM(d.QTY * NVL(d.PRICE, p.SELL_PRICE)), 0) as total_spending
                    ')
                    ->groupBy('c.CLIENT_NO', 'c.CLIENT_NAME')
                    ->orderByRaw('NVL(SUM(d.QTY * NVL(d.PRICE, p.SELL_PRICE)), 0) DESC')
                    ->limit(10)
                    ->get()
                    ->map(static function (object $row): array {
                        return [
                            'client_no'      => (string) ($row->client_no ?? ''),
                            'client_name'    => (string) ($row->client_name ?? ''),
                            'order_count'    => (int) ($row->order_count ?? 0),
                            'total_spending' => round((float) ($row->total_spending ?? 0), 2),
                        ];
                    })
                    ->all();
            });

            return response()->json(['top_clients' => $rows]);
        }

        // ── Summary KPI cards ────────────────────────────────────────────────
        $cacheKey = 'dss_client_summary_v1';
        $summary = Cache::remember($cacheKey, 300, static function () use ($conn): array {
            // Highest spending client
            $top = $conn->table('INVOICES as i')
                ->join('CLIENTS as c', 'c.CLIENT_NO', '=', 'i.CLIENT_NO')
                ->leftJoin('INVOICE_DETAILS as d', 'd.INVOICE_NO', '=', 'i.INVOICE_NO')
                ->leftJoin('PRODUCTS as p', 'p.PRODUCT_NO', '=', 'd.PRODUCT_NO')
                ->selectRaw("c.CLIENT_NAME as client_name, NVL(SUM(d.QTY * NVL(d.PRICE, p.SELL_PRICE)), 0) as total_spending")
                ->groupBy('c.CLIENT_NO', 'c.CLIENT_NAME')
                ->orderByRaw('NVL(SUM(d.QTY * NVL(d.PRICE, p.SELL_PRICE)), 0) DESC')
                ->first();

            // Average spending per client
            $avgRow = $conn->table('INVOICES as i')
                ->leftJoin('INVOICE_DETAILS as d', 'd.INVOICE_NO', '=', 'i.INVOICE_NO')
                ->leftJoin('PRODUCTS as p', 'p.PRODUCT_NO', '=', 'd.PRODUCT_NO')
                ->selectRaw('
                    NVL(SUM(d.QTY * NVL(d.PRICE, p.SELL_PRICE)), 0) as grand_total,
                    COUNT(DISTINCT i.CLIENT_NO) as unique_clients
                ')
                ->first();
            $grandTotal    = (float) ($avgRow->grand_total ?? 0);
            $uniqueClients = (int) ($avgRow->unique_clients ?? 0);
            $avgSpending   = $uniqueClients > 0 ? round($grandTotal / $uniqueClients, 2) : 0.0;

            // Total active clients (clients with at least one invoice)
            $activeClients = (int) $conn->table('INVOICES')
                ->selectRaw('COUNT(DISTINCT CLIENT_NO) as cnt')
                ->value('cnt');

            // New clients this month: first invoice placed this calendar month
            $newClientsMonthRow = $conn->selectOne("
                SELECT COUNT(*) as cnt FROM (
                    SELECT CLIENT_NO, MIN(INVOICE_DATE) as first_date
                    FROM INVOICES
                    GROUP BY CLIENT_NO
                ) WHERE TRUNC(first_date, 'MM') = TRUNC(SYSDATE, 'MM')
            ");
            $newClientsMonth = (int) ($newClientsMonthRow->cnt ?? $newClientsMonthRow->CNT ?? 0);

            // Repeat customer rate
            $repeatRow = $conn->selectOne("
                SELECT
                    COUNT(CASE WHEN invoice_count > 1 THEN 1 END) as repeat_clients,
                    COUNT(*) as total_clients
                FROM (
                    SELECT CLIENT_NO, COUNT(*) as invoice_count
                    FROM INVOICES
                    GROUP BY CLIENT_NO
                )
            ");
            $repeatClients = (int) ($repeatRow->repeat_clients ?? $repeatRow->REPEAT_CLIENTS ?? 0);
            $totalWithInv  = (int) ($repeatRow->total_clients ?? $repeatRow->TOTAL_CLIENTS ?? 0);
            $repeatRate    = $totalWithInv > 0 ? round(($repeatClients / $totalWithInv) * 100, 1) : 0.0;

            // Top 5 clients by revenue
            $top5 = $conn->table('INVOICES as i')
                ->join('CLIENTS as c', 'c.CLIENT_NO', '=', 'i.CLIENT_NO')
                ->leftJoin('INVOICE_DETAILS as d', 'd.INVOICE_NO', '=', 'i.INVOICE_NO')
                ->leftJoin('PRODUCTS as p', 'p.PRODUCT_NO', '=', 'd.PRODUCT_NO')
                ->selectRaw("
                    c.CLIENT_NAME as client_name,
                    NVL(SUM(d.QTY * NVL(d.PRICE, p.SELL_PRICE)), 0) as revenue
                ")
                ->groupBy('c.CLIENT_NO', 'c.CLIENT_NAME')
                ->orderByRaw('NVL(SUM(d.QTY * NVL(d.PRICE, p.SELL_PRICE)), 0) DESC')
                ->limit(5)
                ->get()
                ->map(static function (object $row): array {
                    return [
                        'client_name' => (string) ($row->client_name ?? ''),
                        'revenue'     => round((float) ($row->revenue ?? 0), 2),
                    ];
                })
                ->all();

            return [
                'highest_spending_client' => (string) ($top->client_name ?? 'N/A'),
                'highest_spending_amount' => round((float) ($top->total_spending ?? 0), 2),
                'avg_spending'            => $avgSpending,
                'active_clients'          => $activeClients,
                'new_clients_month'       => $newClientsMonth,
                'repeat_rate'             => $repeatRate,
                'clv'                     => $avgSpending,
                'top5'                    => $top5,
                'grand_total'             => round($grandTotal, 2),
            ];
        });

        return response()->json($summary);
    }

    public function updateClient(Request $request, int $clientNo): RedirectResponse
    {
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:15'],
            'address' => ['nullable', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:50'],
            'client_type' => ['nullable', 'integer'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $clientTypeId = $validated['client_type'] ?? null;
        if ($clientTypeId !== null) {
            $exists = DB::connection('oracle')
                ->table('CLIENT_TYPE')
                ->where('CLIENTTYPE_ID', '=', (int) $clientTypeId)
                ->exists();
            if (! $exists) {
                return back()->with('error', 'Client type not found.');
            }
        }

        DB::connection('oracle')
            ->table('CLIENTS')
            ->where('CLIENT_NO', '=', $clientNo)
            ->update([
                'CLIENT_NAME' => (string) $validated['client_name'],
                'PHONE' => (string) $validated['phone'],
                'ADDRESS' => ($validated['address'] ?? '') !== '' ? (string) $validated['address'] : null,
                'CITY' => ($validated['city'] ?? '') !== '' ? (string) $validated['city'] : null,
                'CLIENT_TYPE' => $clientTypeId !== null ? (int) $clientTypeId : null,
                'DISCOUNT' => $validated['discount'] ?? null,
            ]);

        return back()->with('success', "Client #{$clientNo} updated.");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:15'],
            'address' => ['nullable', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:50'],
            'client_type' => ['nullable', 'integer'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $clientTypeId = $validated['client_type'] ?? null;
        if ($clientTypeId !== null) {
            $exists = DB::connection('oracle')
                ->table('CLIENT_TYPE')
                ->where('CLIENTTYPE_ID', '=', (int) $clientTypeId)
                ->exists();
            if (! $exists) {
                return back()->withInput()->withErrors(['client_type' => 'Client type not found.']);
            }
        }

        try {
            DB::connection('oracle')->insert(
                'INSERT INTO CLIENTS (CLIENT_NAME, PHONE, ADDRESS, CITY, CLIENT_TYPE, DISCOUNT)
                 VALUES (:client_name, :phone, :address, :city, :client_type, :discount)',
                [
                    'client_name' => (string) $validated['client_name'],
                    'phone' => (string) $validated['phone'],
                    'address' => ($validated['address'] ?? '') !== '' ? (string) $validated['address'] : null,
                    'city' => ($validated['city'] ?? '') !== '' ? (string) $validated['city'] : null,
                    'client_type' => $clientTypeId !== null ? (int) $clientTypeId : null,
                    'discount' => $validated['discount'] ?? null,
                ]
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to create client: '.$e->getMessage());
        }

        return back()->with('success', 'Client created.');
    }

    public function createClientType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type_name' => ['required', 'string', 'max:40'],
            'discount_rate' => ['required', 'numeric', 'min:0'],
        ]);

        DB::connection('oracle')->insert(
            'INSERT INTO CLIENT_TYPE (TYPE_NAME, DISCOUNT_RATE) VALUES (:type_name, :discount_rate)',
            [
                'type_name' => (string) $validated['type_name'],
                'discount_rate' => (float) $validated['discount_rate'],
            ]
        );

        return back()->with('success', 'Client type created.');
    }

    public function updateClientType(Request $request, int $clientTypeId): RedirectResponse
    {
        $validated = $request->validate([
            'type_name' => ['required', 'string', 'max:40'],
            'discount_rate' => ['required', 'numeric', 'min:0'],
        ]);

        DB::connection('oracle')
            ->table('CLIENT_TYPE')
            ->where('CLIENTTYPE_ID', '=', $clientTypeId)
            ->update([
                'TYPE_NAME' => (string) $validated['type_name'],
                'DISCOUNT_RATE' => (float) $validated['discount_rate'],
            ]);

        return back()->with('success', 'Client type updated.');
    }
}

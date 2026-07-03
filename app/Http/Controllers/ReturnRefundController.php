<?php

namespace App\Http\Controllers;

use App\Support\StaffAuth;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class ReturnRefundController extends Controller
{
    private const ORACLE_CONNECTION = 'oracle';

    /**
     * Cache for Oracle identity sequence names.
     *
     * @var array<string, string>
     */
    private array $identitySequences = [];

    public function index(Request $request): View
    {
        $conn = $this->db();
        $q = trim((string) $request->query('q', ''));
        $selectedInvoiceNo = (int) $request->old('invoice_no', $request->query('invoice_no', 0));

        $returnsQuery = $conn->table('RETURNS as r')
            ->join('INVOICES as i', 'i.INVOICE_NO', '=', 'r.INVOICE_NO')
            ->leftJoin('CLIENTS as c', 'c.CLIENT_NO', '=', 'i.CLIENT_NO')
            ->leftJoin('RETURN_DETAILS as rd', 'rd.RETURN_NO', '=', 'r.RETURN_NO')
            ->selectRaw('
                r.RETURN_NO as return_no,
                r.INVOICE_NO as invoice_no,
                r.RETURN_DATE as return_date,
                r.REASON as reason,
                r.STATUS as status,
                c.CLIENT_NAME as client_name,
                c.PHONE as phone,
                NVL(SUM(NVL(rd.QTY, 0)), 0) as item_qty,
                NVL(SUM(NVL(rd.REFUND_AMOUNT, 0)), 0) as refund_total
            ')
            ->groupBy('r.RETURN_NO', 'r.INVOICE_NO', 'r.RETURN_DATE', 'r.REASON', 'r.STATUS', 'c.CLIENT_NAME', 'c.PHONE');

        if ($q !== '') {
            $keyword = '%'.mb_strtoupper($q).'%';
            $returnsQuery->where(function ($sub) use ($q, $keyword): void {
                if (ctype_digit($q)) {
                    $sub->where('r.RETURN_NO', '=', (int) $q)
                        ->orWhere('r.INVOICE_NO', '=', (int) $q)
                        ->orWhereRaw('c.PHONE LIKE ?', ['%'.$q.'%'])
                        ->orWhereRaw('UPPER(c.CLIENT_NAME) LIKE ?', [$keyword]);
                } else {
                    $sub->whereRaw('UPPER(c.CLIENT_NAME) LIKE ?', [$keyword])
                        ->orWhereRaw('UPPER(r.REASON) LIKE ?', [$keyword])
                        ->orWhereRaw('UPPER(r.STATUS) LIKE ?', [$keyword]);
                }
            });
        }

        $returns = $returnsQuery
            ->orderByDesc('r.RETURN_NO')
            ->paginate(15)
            ->appends($request->query());

        $recentInvoices = $conn->table('INVOICES as i')
            ->leftJoin('CLIENTS as c', 'c.CLIENT_NO', '=', 'i.CLIENT_NO')
            ->selectRaw('
                i.INVOICE_NO as invoice_no,
                i.INVOICE_DATE as invoice_date,
                c.CLIENT_NAME as client_name,
                c.PHONE as phone
            ')
            ->orderByDesc('i.INVOICE_NO')
            ->limit(100)
            ->get();

        $selectedInvoice = $selectedInvoiceNo > 0 ? $this->invoicePayload($selectedInvoiceNo) : null;

        return view('ecommerce.returns', [
            'returns' => $returns,
            'q' => $q,
            'recentInvoices' => $recentInvoices,
            'selectedInvoiceNo' => $selectedInvoiceNo > 0 ? $selectedInvoiceNo : null,
            'selectedInvoice' => $selectedInvoice,
            'returnColumns' => $this->tableMetadata('RETURNS'),
            'returnDetailColumns' => $this->tableMetadata('RETURN_DETAILS'),
            'canManageReturns' => StaffAuth::can('returns.manage'),
            'inProcessCount' => $this->countInProcessInvoices(),
        ]);
    }

    public function invoice(int $invoiceNo): JsonResponse
    {
        $payload = $this->invoicePayload($invoiceNo);
        if ($payload === null) {
            return response()->json(['message' => "Invoice #{$invoiceNo} was not found."], 404);
        }

        return response()->json($payload);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_no' => ['required', 'integer'],
            'return_date' => ['nullable', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:20'],
            'items' => ['required', 'array'],
            'items.*.product_no' => ['required_with:items', 'string', 'max:20'],
            'items.*.qty' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'items.*.refund_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $invoiceNo = (int) $validated['invoice_no'];
        $invoice = $this->invoicePayload($invoiceNo);
        if ($invoice === null) {
            return back()->withInput()->with('error', "Invoice #{$invoiceNo} was not found.");
        }

        $items = collect($validated['items'] ?? [])
            ->map(static function (array $item): array {
                return [
                    'product_no' => trim((string) ($item['product_no'] ?? '')),
                    'qty' => (int) ($item['qty'] ?? 0),
                    'refund_amount' => round((float) ($item['refund_amount'] ?? 0), 2),
                ];
            })
            ->filter(static fn (array $item): bool => $item['product_no'] !== '' && $item['qty'] > 0)
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Add at least one returned product quantity.');
        }

        $upperProductNos = $items
            ->map(static fn (array $item): string => mb_strtoupper($item['product_no']))
            ->all();
        if (count($upperProductNos) !== count(array_unique($upperProductNos))) {
            return back()->withInput()->with('error', 'Duplicate product numbers were added.');
        }

        $invoiceItems = collect($invoice['items'] ?? [])
            ->keyBy(static fn (array $item): string => mb_strtoupper((string) ($item['product_no'] ?? '')));

        foreach ($items as $item) {
            $productKey = mb_strtoupper((string) $item['product_no']);
            $invoiceItem = $invoiceItems->get($productKey);
            if (! $invoiceItem) {
                return back()->withInput()->with('error', "Product {$item['product_no']} does not belong to invoice #{$invoiceNo}.");
            }

            $returnableQty = (int) ($invoiceItem['returnable_qty'] ?? 0);
            if ((int) $item['qty'] > $returnableQty) {
                return back()->withInput()->with('error', "Only {$returnableQty} unit(s) of product {$item['product_no']} can still be returned.");
            }
        }

        $returnDate = (string) ($validated['return_date'] ?? '');
        if ($returnDate === '') {
            $returnDate = now()->format('Y-m-d');
        }
        $status = trim((string) ($validated['status'] ?? ''));
        if ($status === '') {
            $status = 'Refunded';
        }

        try {
            $returnNo = null;
            $this->db()->transaction(function () use ($invoiceNo, $returnDate, $status, $validated, $items, &$returnNo): void {
                $this->insertReturnHeader($invoiceNo, $returnDate, trim((string) ($validated['reason'] ?? '')), $status);
                $returnNo = $this->currentSequenceValue($this->identitySequence('RETURNS', 'RETURN_NO'));

                foreach ($items as $item) {
                    $this->insertReturnDetail(
                        (int) $returnNo,
                        (string) $item['product_no'],
                        (int) $item['qty'],
                        (float) $item['refund_amount']
                    );

                    $this->db()->statement(
                        'UPDATE PRODUCTS SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) + :qty WHERE PRODUCT_NO = :product_no',
                        [
                            'qty' => (int) $item['qty'],
                            'product_no' => (string) $item['product_no'],
                        ]
                    );
                }
            }, 3);
        } catch (QueryException $e) {
            return back()->withInput()->with('error', 'Unable to save return: '.$e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Unable to save return: '.$e->getMessage());
        }

        return redirect()
            ->route('returns.index', ['invoice_no' => $invoiceNo])
            ->with('success', "Return #{$returnNo} saved and stock was updated.");
    }

    private function insertReturnHeader(int $invoiceNo, string $returnDate, string $reason, string $status): void
    {
        $columns = $this->tableColumnNames('RETURNS');
        $insertColumns = [];
        $values = [];
        $bindings = [];

        if (in_array('INVOICE_NO', $columns, true)) {
            $insertColumns[] = 'INVOICE_NO';
            $values[] = ':invoice_no';
            $bindings['invoice_no'] = $invoiceNo;
        }
        if (in_array('RETURN_DATE', $columns, true)) {
            $insertColumns[] = 'RETURN_DATE';
            $values[] = "TO_DATE(:return_date, 'YYYY-MM-DD')";
            $bindings['return_date'] = $returnDate;
        }
        if (in_array('REASON', $columns, true)) {
            $insertColumns[] = 'REASON';
            $values[] = ':reason';
            $bindings['reason'] = $reason !== '' ? $reason : null;
        }
        if (in_array('STATUS', $columns, true)) {
            $insertColumns[] = 'STATUS';
            $values[] = ':status';
            $bindings['status'] = $status;
        }

        if ($insertColumns === []) {
            throw new RuntimeException('RETURNS schema is missing insertable columns.');
        }

        $this->db()->insert(
            'INSERT INTO RETURNS ('.implode(', ', $insertColumns).') VALUES ('.implode(', ', $values).')',
            $bindings
        );
    }

    private function insertReturnDetail(int $returnNo, string $productNo, int $qty, float $refundAmount): void
    {
        $columns = $this->tableColumnNames('RETURN_DETAILS');
        $insertColumns = [];
        $values = [];
        $bindings = [];

        if (in_array('RETURN_NO', $columns, true)) {
            $insertColumns[] = 'RETURN_NO';
            $values[] = ':return_no';
            $bindings['return_no'] = $returnNo;
        }
        if (in_array('PRODUCT_NO', $columns, true)) {
            $insertColumns[] = 'PRODUCT_NO';
            $values[] = ':product_no';
            $bindings['product_no'] = $productNo;
        }
        if (in_array('QTY', $columns, true)) {
            $insertColumns[] = 'QTY';
            $values[] = ':qty';
            $bindings['qty'] = $qty;
        }
        if (in_array('REFUND_AMOUNT', $columns, true)) {
            $insertColumns[] = 'REFUND_AMOUNT';
            $values[] = ':refund_amount';
            $bindings['refund_amount'] = $refundAmount;
        }

        foreach (['RETURN_NO', 'PRODUCT_NO'] as $requiredColumn) {
            if (! in_array($requiredColumn, $insertColumns, true)) {
                throw new RuntimeException("RETURN_DETAILS schema is missing {$requiredColumn}.");
            }
        }

        $this->db()->insert(
            'INSERT INTO RETURN_DETAILS ('.implode(', ', $insertColumns).') VALUES ('.implode(', ', $values).')',
            $bindings
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function invoicePayload(int $invoiceNo): ?array
    {
        $conn = $this->db();
        $header = $conn->table('INVOICES as i')
            ->leftJoin('CLIENTS as c', 'c.CLIENT_NO', '=', 'i.CLIENT_NO')
            ->leftJoin('EMPLOYEES as e', 'e.EMPLOYEE_ID', '=', 'i.EMPLOYEE_ID')
            ->selectRaw('
                i.INVOICE_NO as invoice_no,
                i.INVOICE_DATE as invoice_date,
                i.INVOICE_STATUS as invoice_status,
                c.CLIENT_NAME as client_name,
                c.PHONE as phone,
                e.EMPLOYEE_NAME as employee_name
            ')
            ->where('i.INVOICE_NO', '=', $invoiceNo)
            ->first();

        if (! $header) {
            return null;
        }

        $items = $conn->table('INVOICE_DETAILS as d')
            ->join('PRODUCTS as p', 'p.PRODUCT_NO', '=', 'd.PRODUCT_NO')
            ->selectRaw('
                d.PRODUCT_NO as product_no,
                p.PRODUCT_NAME as product_name,
                NVL(d.QTY, 0) as sold_qty,
                NVL(d.PRICE, p.SELL_PRICE) as price
            ')
            ->where('d.INVOICE_NO', '=', $invoiceNo)
            ->orderBy('p.PRODUCT_NAME')
            ->get();

        $returned = $conn->table('RETURNS as r')
            ->join('RETURN_DETAILS as rd', 'rd.RETURN_NO', '=', 'r.RETURN_NO')
            ->selectRaw('rd.PRODUCT_NO as product_no, NVL(SUM(NVL(rd.QTY, 0)), 0) as returned_qty')
            ->where('r.INVOICE_NO', '=', $invoiceNo)
            ->groupBy('rd.PRODUCT_NO')
            ->get()
            ->mapWithKeys(static function (object $row): array {
                return [mb_strtoupper((string) ($row->product_no ?? $row->PRODUCT_NO ?? '')) => (int) ($row->returned_qty ?? $row->RETURNED_QTY ?? 0)];
            });

        $decoratedItems = $items
            ->map(static function (object $row) use ($returned): array {
                $productNo = (string) ($row->product_no ?? '');
                $soldQty = (int) ($row->sold_qty ?? 0);
                $returnedQty = (int) ($returned[mb_strtoupper($productNo)] ?? 0);
                $returnableQty = max(0, $soldQty - $returnedQty);
                $price = round((float) ($row->price ?? 0), 2);

                return [
                    'product_no' => $productNo,
                    'product_name' => (string) ($row->product_name ?? ''),
                    'sold_qty' => $soldQty,
                    'returned_qty' => $returnedQty,
                    'returnable_qty' => $returnableQty,
                    'price' => $price,
                    'line_total' => round($soldQty * $price, 2),
                ];
            })
            ->values();

        return [
            'header' => [
                'invoice_no' => (int) ($header->invoice_no ?? 0),
                'invoice_date' => $header->invoice_date ? Carbon::parse($header->invoice_date)->format('Y-m-d H:i') : '',
                'invoice_status' => (string) ($header->invoice_status ?? ''),
                'client_name' => (string) ($header->client_name ?? ''),
                'phone' => (string) ($header->phone ?? ''),
                'employee_name' => (string) ($header->employee_name ?? ''),
            ],
            'items' => $decoratedItems->all(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function tableMetadata(string $table): Collection
    {
        return $this->db()->table('USER_TAB_COLUMNS')
            ->selectRaw('COLUMN_NAME as column_name, DATA_TYPE as data_type, DATA_LENGTH as data_length, NULLABLE as nullable, DATA_DEFAULT as data_default')
            ->where('TABLE_NAME', '=', mb_strtoupper($table))
            ->orderBy('COLUMN_ID')
            ->get()
            ->map(function (object $row): array {
                $name = mb_strtoupper((string) ($row->column_name ?? $row->COLUMN_NAME ?? ''));
                $type = mb_strtoupper((string) ($row->data_type ?? $row->DATA_TYPE ?? ''));
                $default = trim((string) ($row->data_default ?? $row->DATA_DEFAULT ?? ''));

                return [
                    'name' => $name,
                    'input_name' => mb_strtolower($name),
                    'label' => ucwords(mb_strtolower(str_replace('_', ' ', $name))),
                    'data_type' => $type,
                    'data_length' => (int) ($row->data_length ?? $row->DATA_LENGTH ?? 0),
                    'nullable' => ((string) ($row->nullable ?? $row->NULLABLE ?? 'Y')) === 'Y',
                    'default' => $default,
                    'is_identity' => str_contains($default, 'NEXTVAL') || $name === 'RETURN_NO',
                    'input_type' => $this->inputTypeForColumn($name, $type),
                ];
            })
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private function tableColumnNames(string $table): array
    {
        return $this->tableMetadata($table)
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }

    private function inputTypeForColumn(string $name, string $type): string
    {
        if ($type === 'DATE' || str_contains($type, 'TIMESTAMP')) {
            return 'date';
        }
        if ($type === 'NUMBER') {
            return str_contains($name, 'AMOUNT') ? 'money' : 'number';
        }
        if ($name === 'REASON') {
            return 'textarea';
        }

        return 'text';
    }

    private function countInProcessInvoices(): int
    {
        try {
            return (int) $this->db()->table('INVOICES')
                ->where('INVOICE_STATUS', '=', 'In Process')
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function identitySequence(string $tableName, string $columnName): string
    {
        $key = strtoupper($tableName).'.'.strtoupper($columnName);
        if (isset($this->identitySequences[$key])) {
            return $this->identitySequences[$key];
        }

        $row = $this->db()->selectOne(
            'SELECT SEQUENCE_NAME FROM USER_TAB_IDENTITY_COLS WHERE TABLE_NAME = :table_name AND COLUMN_NAME = :column_name',
            [
                'table_name' => strtoupper($tableName),
                'column_name' => strtoupper($columnName),
            ]
        );

        $sequence = $row->sequence_name ?? $row->SEQUENCE_NAME ?? null;
        if (! is_string($sequence) || $sequence === '') {
            throw new RuntimeException("Identity sequence not found for {$key}.");
        }

        $this->identitySequences[$key] = $sequence;

        return $sequence;
    }

    private function currentSequenceValue(string $sequence): int
    {
        $row = $this->db()->selectOne('SELECT '.$sequence.'.CURRVAL AS ID FROM DUAL');
        $value = $row->id ?? $row->ID ?? null;

        return (int) $value;
    }

    private function db()
    {
        return DB::connection(self::ORACLE_CONNECTION);
    }
}

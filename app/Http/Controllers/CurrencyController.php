<?php

namespace App\Http\Controllers;

use App\Support\StaffAuth;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $schema = $this->currencySchema();

        $currencies = new LengthAwarePaginator([], 0, 15);
        $metrics = [
            'total' => 0,
            'active' => 0,
            'avg_rate' => 0.0,
        ];

        if ($schema['exists']) {
            $conn = DB::connection('oracle');
            $query = $conn->table('CURRENCIES as c');

            $select = ['ROWIDTOCHAR(c.ROWID) as row_token'];
            if ($schema['id'] !== null) {
                $select[] = 'c.'.$schema['id'].' as currency_id';
            }
            if ($schema['code'] !== null) {
                $select[] = 'c.'.$schema['code'].' as currency_code';
            }
            if ($schema['name'] !== null) {
                $select[] = 'c.'.$schema['name'].' as currency_name';
            }
            if ($schema['symbol'] !== null) {
                $select[] = 'c.'.$schema['symbol'].' as symbol';
            }
            if ($schema['rate'] !== null) {
                $select[] = 'c.'.$schema['rate'].' as exchange_rate';
            }
            if ($schema['status'] !== null) {
                $select[] = 'c.'.$schema['status'].' as status';
            }

            $query->selectRaw(implode(', ', $select));
            $this->applySearch($query, $q, $schema);

            if ($schema['name'] !== null) {
                $query->orderBy('c.'.$schema['name']);
            } elseif ($schema['code'] !== null) {
                $query->orderBy('c.'.$schema['code']);
            } elseif ($schema['id'] !== null) {
                $query->orderBy('c.'.$schema['id']);
            } else {
                $query->orderByRaw('ROWID');
            }

            $currencies = $query->paginate(15)->appends($request->query());

            try {
                $metrics['total'] = (int) $conn->table('CURRENCIES')->count();
            } catch (\Throwable) {
                $metrics['total'] = (int) $currencies->total();
            }

            if ($schema['status'] !== null) {
                try {
                    $metrics['active'] = (int) $conn->table('CURRENCIES')
                        ->whereRaw(
                            "UPPER(NVL(TRIM(TO_CHAR({$schema['status']})), '')) IN ('ACTIVE', 'A', 'YES', 'Y', 'TRUE', '1')"
                        )
                        ->count();
                } catch (\Throwable) {
                    $metrics['active'] = 0;
                }
            } else {
                $metrics['active'] = (int) $metrics['total'];
            }

            if ($schema['rate'] !== null) {
                try {
                    $metrics['avg_rate'] = (float) ($conn->table('CURRENCIES')
                        ->selectRaw("NVL(AVG({$schema['rate']}), 0) as avg_rate")
                        ->value('avg_rate') ?? 0);
                } catch (\Throwable) {
                    $metrics['avg_rate'] = 0.0;
                }
            }
        }

        return view('currencies.index', [
            'currencies' => $currencies,
            'q' => $q,
            'schema' => $schema,
            'metrics' => $metrics,
            'canManageCurrencies' => StaffAuth::can('currencies.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schema = $this->currencySchema();
        if (! $schema['exists']) {
            return back()->with('error', 'CURRENCIES table was not found.');
        }

        $rules = $this->currencyRules($schema, true);
        if ($rules === []) {
            return back()->with('error', 'No compatible currency columns were found for insert.');
        }

        $validated = $request->validate($rules);
        $payload = $this->buildPayload($validated, $schema, true);

        if ($payload === []) {
            return back()->withInput()->with('error', 'No currency data was provided.');
        }

        $conn = DB::connection('oracle');

        try {
            $conn->table('CURRENCIES')->insert($payload);
        } catch (QueryException $e) {
            if ($this->needsPrimaryKeyFallback($e, $schema) && $schema['id'] !== null && ! isset($payload[$schema['id']])) {
                $nextId = (int) $conn->table('CURRENCIES')->max($schema['id']) + 1;
                if ($nextId <= 0) {
                    $nextId = 1;
                }

                try {
                    $conn->table('CURRENCIES')->insert(array_merge($payload, [
                        $schema['id'] => $nextId,
                    ]));

                    return back()->with('success', 'Currency created.');
                } catch (QueryException $nested) {
                    return back()->withInput()->with('error', 'Failed to create currency: '.$this->oracleErrorMessage($nested));
                }
            }

            return back()->withInput()->with('error', 'Failed to create currency: '.$this->oracleErrorMessage($e));
        }

        return back()->with('success', 'Currency created.');
    }

    public function update(Request $request): RedirectResponse
    {
        $schema = $this->currencySchema();
        if (! $schema['exists']) {
            return back()->with('error', 'CURRENCIES table was not found.');
        }

        $rules = array_merge(
            ['row_token' => ['required', 'string', 'max:64']],
            $this->currencyRules($schema, false)
        );
        $validated = $request->validate($rules);

        $payload = $this->buildPayload($validated, $schema, false);
        if ($payload === []) {
            return back()->with('error', 'No currency fields were changed.');
        }

        try {
            $updated = DB::connection('oracle')
                ->table('CURRENCIES')
                ->whereRaw('ROWIDTOCHAR(ROWID) = ?', [(string) $validated['row_token']])
                ->update($payload);
        } catch (QueryException $e) {
            return back()->with('error', 'Failed to update currency: '.$this->oracleErrorMessage($e));
        }

        if (! $updated) {
            return back()->with('error', 'Currency row was not found.');
        }

        return back()->with('success', 'Currency updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $schema = $this->currencySchema();
        if (! $schema['exists']) {
            return back()->with('error', 'CURRENCIES table was not found.');
        }

        $validated = $request->validate([
            'row_token' => ['required', 'string', 'max:64'],
        ]);

        try {
            $deleted = DB::connection('oracle')
                ->table('CURRENCIES')
                ->whereRaw('ROWIDTOCHAR(ROWID) = ?', [(string) $validated['row_token']])
                ->delete();
        } catch (QueryException $e) {
            return back()->with('error', 'Failed to delete currency: '.$this->oracleErrorMessage($e));
        }

        if (! $deleted) {
            return back()->with('error', 'Currency row was not found.');
        }

        return back()->with('success', 'Currency deleted.');
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function currencyRules(array $schema, bool $forStore): array
    {
        $required = $forStore ? ['required'] : ['nullable'];

        $rules = [];
        if ($schema['code'] !== null) {
            $rules['currency_code'] = array_merge($required, ['string', 'max:20']);
        }
        if ($schema['name'] !== null) {
            $rules['currency_name'] = array_merge($required, ['string', 'max:80']);
        }
        if ($schema['symbol'] !== null) {
            $rules['symbol'] = ['nullable', 'string', 'max:12'];
        }
        if ($schema['rate'] !== null) {
            $rules['exchange_rate'] = ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'];
        }
        if ($schema['status'] !== null) {
            $rules['status'] = ['nullable', 'string', 'max:20'];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function buildPayload(array $validated, array $schema, bool $forStore): array
    {
        $payload = [];

        if ($schema['code'] !== null && array_key_exists('currency_code', $validated)) {
            $value = trim((string) $validated['currency_code']);
            $payload[$schema['code']] = $value !== '' ? $value : null;
        }
        if ($schema['name'] !== null && array_key_exists('currency_name', $validated)) {
            $value = trim((string) $validated['currency_name']);
            $payload[$schema['name']] = $value !== '' ? $value : null;
        }
        if ($schema['symbol'] !== null && array_key_exists('symbol', $validated)) {
            $value = trim((string) $validated['symbol']);
            $payload[$schema['symbol']] = $value !== '' ? $value : null;
        }
        if ($schema['rate'] !== null && array_key_exists('exchange_rate', $validated)) {
            $payload[$schema['rate']] = $validated['exchange_rate'] !== null && $validated['exchange_rate'] !== ''
                ? round((float) $validated['exchange_rate'], 2)
                : null;
        }
        if ($schema['status'] !== null && array_key_exists('status', $validated)) {
            $value = trim((string) $validated['status']);
            $payload[$schema['status']] = $value !== '' ? $value : null;
        }

        if ($forStore && $schema['status'] !== null && ! array_key_exists($schema['status'], $payload)) {
            $payload[$schema['status']] = 'Active';
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function applySearch($query, string $q, array $schema): void
    {
        if ($q === '') {
            return;
        }

        $searchableColumns = array_values(array_filter([
            $schema['id'],
            $schema['code'],
            $schema['name'],
            $schema['symbol'],
            $schema['rate'],
            $schema['status'],
        ]));

        if ($searchableColumns === []) {
            return;
        }

        $keyword = '%'.mb_strtoupper($q).'%';
        $query->where(function ($sub) use ($searchableColumns, $keyword): void {
            foreach ($searchableColumns as $index => $column) {
                $fragment = "UPPER(NVL(TO_CHAR(c.{$column}), '')) LIKE ?";
                if ($index === 0) {
                    $sub->whereRaw($fragment, [$keyword]);
                } else {
                    $sub->orWhereRaw($fragment, [$keyword]);
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function currencySchema(): array
    {
        $exists = $this->tableExists('CURRENCIES');
        $columns = $exists ? $this->tableColumns('CURRENCIES') : [];

        return [
            'exists' => $exists,
            'columns' => $columns,
            'id' => $this->pickColumn($columns, ['CURRENCY_ID', 'CURRENCY_NO', 'ID', 'CUR_ID']),
            'code' => $this->pickColumn($columns, ['CURRENCY_CODE', 'CODE', 'CURR_CODE', 'ISO_CODE']),
            'name' => $this->pickColumn($columns, ['CURRENCY_NAME', 'NAME', 'CURR_NAME']),
            'symbol' => $this->pickColumn($columns, ['SYMBOL', 'SIGN', 'CURRENCY_SYMBOL']),
            'rate' => $this->pickColumn($columns, [
                'EXCHANGE_RATE_TO_USD',
                'EXCHANGE_RATE',
                'RATE',
                'EX_RATE',
                'USD_RATE',
                'RATE_TO_USD',
            ]),
            'status' => $this->pickColumn($columns, ['STATUS', 'ACTIVE', 'IS_ACTIVE']),
        ];
    }

    private function tableExists(string $table): bool
    {
        $row = DB::connection('oracle')->selectOne(
            'SELECT COUNT(*) AS CNT FROM USER_TABLES WHERE TABLE_NAME = :table_name',
            ['table_name' => mb_strtoupper($table)]
        );

        $count = (int) ($row->cnt ?? $row->CNT ?? 0);

        return $count > 0;
    }

    /**
     * @return array<int, string>
     */
    private function tableColumns(string $table): array
    {
        $rows = DB::connection('oracle')
            ->table('USER_TAB_COLUMNS')
            ->selectRaw('COLUMN_NAME as column_name')
            ->where('TABLE_NAME', '=', mb_strtoupper($table))
            ->orderBy('COLUMN_ID')
            ->get();

        return $rows
            ->map(static fn (object $row): string => mb_strtoupper((string) ($row->column_name ?? $row->COLUMN_NAME ?? '')))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $candidates
     */
    private function pickColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $upper = mb_strtoupper($candidate);
            if (in_array($upper, $columns, true)) {
                return $upper;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function needsPrimaryKeyFallback(QueryException $e, array $schema): bool
    {
        if ($schema['id'] === null) {
            return false;
        }

        $message = mb_strtoupper($e->getMessage());

        return str_contains($message, 'ORA-01400') && str_contains($message, mb_strtoupper($schema['id']));
    }

    private function oracleErrorMessage(QueryException $e): string
    {
        $message = mb_strtoupper($e->getMessage());

        if (str_contains($message, 'ORA-00001')) {
            return 'Currency values must be unique.';
        }
        if (str_contains($message, 'ORA-01400')) {
            return 'A required currency field is missing.';
        }
        if (str_contains($message, 'ORA-02290')) {
            return 'Currency data failed a validation rule.';
        }
        if (str_contains($message, 'ORA-02292')) {
            return 'Currency cannot be deleted because it is referenced by other records.';
        }

        return 'Please check the currency input values.';
    }
}

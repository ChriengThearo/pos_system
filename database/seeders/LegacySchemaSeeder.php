<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Reproduces the exact row data captured from the C##website_v3 schema.
 *
 * The legacy schema has triggers (SALE_ADD, ADD_PURCHASE_QTY, ALERT_STOCKS,
 * CHECK_SALARY, PAYMENT_CALCULATE_DEBT, ...) that recompute columns such as
 * QTY_ON_HAND, PRICE and DEBT_AMOUNT as a side effect of INSERT. Since the
 * dumped data already contains those final computed values, the triggers
 * are disabled for the duration of the seed so the historical snapshot is
 * inserted verbatim instead of being recalculated.
 */
class LegacySchemaSeeder extends Seeder
{
    private const TABLES_IN_ORDER = [
        'CLIENT_TYPE', 'CURRENCIES', 'FORM_CONTOL', 'GROUP_USER', 'JOBS',
        'PRODUCT_MEASURE', 'PRODUCT_TYPE', 'SUPPLIERS',
        'CLIENTS', 'EMPLOYEES', 'PRODUCTS',
        'ALERT_STOCKS', 'USERS', 'INVOICES', 'PRODUCT_PHOTO', 'PURCHASES', 'PERMISSION_GROUP',
        'INVOICE_DETAILS', 'PURCHASE_DETAILS', 'PAYMENTS',
    ];

    private const TRIGGERS = [
        'ADD_PURCHASE_QTY', 'ADD_PURCHASE_UNITCOST', 'ALERT_STOCKS', 'CHECK_SALARY',
        'PAYMENTS_CALCULATE_USD', 'PAYMENT_CALCULATE_DEBT', 'SALE_ADD', 'SALE_DELETE', 'SALE_UPDATE',
    ];

    public function run(): void
    {
        foreach (self::TRIGGERS as $trigger) {
            DB::unprepared("ALTER TRIGGER \"{$trigger}\" DISABLE");
        }

        try {
            foreach (self::TABLES_IN_ORDER as $table) {
                $this->seedTable($table);
            }
        } finally {
            foreach (self::TRIGGERS as $trigger) {
                DB::unprepared("ALTER TRIGGER \"{$trigger}\" ENABLE");
            }
        }
    }

    private function seedTable(string $table): void
    {
        $path = database_path("seeders/data/{$table}.sql");
        if (! file_exists($path)) {
            return;
        }

        $statements = array_filter(array_map('trim', file($path)));

        foreach ($statements as $statement) {
            DB::unprepared($statement);
        }
    }
}

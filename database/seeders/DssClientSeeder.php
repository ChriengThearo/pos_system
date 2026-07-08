<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds 50 invoices per client for the top 10 clients.
 * Each invoice has 4 random line items, qty 5–10 each, with PRICE filled.
 * Invoice dates are random between 2023-12-20 and 2026-05-20.
 *
 * Run:  php artisan db:seed --class=DssClientSeeder
 * Reset: DELETE FROM INVOICE_DETAILS WHERE INVOICE_NO IN (SELECT INVOICE_NO FROM INVOICES WHERE INVOICE_MEMO = 'DSS seed');
 *        DELETE FROM INVOICES WHERE INVOICE_MEMO = 'DSS seed';
 */
class DssClientSeeder extends Seeder
{
    /** Oracle identity sequence for INVOICES.INVOICE_NO */
    private const INVOICE_SEQ = 'ISEQ$$_93041';

    /** Invoices per client */
    private const INVOICES_PER_CLIENT = 50;

    /** Line items per invoice */
    private const ITEMS_PER_INVOICE = 4;

    /** Qty range per line item */
    private const QTY_MIN = 5;
    private const QTY_MAX = 10;

    /** Date range */
    private const DATE_FROM = '2023-12-20';
    private const DATE_TO   = '2026-07-07';   // today — ensures 7d/30d/90d all have data

    /** Memo tag — used to detect / clean up this seed */
    private const MEMO = 'DSS seed';

    private const EMPLOYEE_IDS = [92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105];

    private const PRODUCTS = [
        ['P0001', 857.89],
        ['P0002', 234.84],
        ['P0003', 918.66],
        ['P0004', 785.46],
        ['P0005', 512.12],
        ['P0006', 284.05],
        ['P0007', 904.20],
        ['P0008', 1370.07],
        ['P0009', 1216.60],
        ['P0010', 1047.84],
        ['P0011', 950.40],
        ['P0012', 787.36],
        ['P0013', 460.00],
        ['P0014', 379.08],
        ['P0015', 303.92],
        ['P0016', 60.95],
        ['P0017', 45.36],
        ['P0018', 61.56],
        ['P0019', 13.92],
        ['P0020', 22.00],
    ];

    private const TRIGGERS = [
        'ADD_PURCHASE_QTY', 'ADD_PURCHASE_UNITCOST', 'ALERT_STOCKS', 'CHECK_SALARY',
        'PAYMENTS_CALCULATE_USD', 'PAYMENT_CALCULATE_DEBT', 'SALE_ADD', 'SALE_DELETE', 'SALE_UPDATE',
    ];

    public function run(): void
    {
        $conn = DB::connection('oracle');

        // Idempotency check
        $already = (int) $conn->table('INVOICES')
            ->where('INVOICE_MEMO', '=', self::MEMO)
            ->count();

        if ($already > 0) {
            $this->command?->info("DssClientSeeder: already seeded ({$already} invoices). Skipping.");
            return;
        }

        // Disable triggers so QTY_ON_HAND is not touched
        foreach (self::TRIGGERS as $t) {
            try { $conn->unprepared("ALTER TRIGGER \"{$t}\" DISABLE"); } catch (\Throwable) {}
        }

        try {
            $this->seed($conn);
        } finally {
            foreach (self::TRIGGERS as $t) {
                try { $conn->unprepared("ALTER TRIGGER \"{$t}\" ENABLE"); } catch (\Throwable) {}
            }
            // Bust the DSS cache
            try {
                \Illuminate\Support\Facades\Cache::forget('dss_top_clients_v1');
                \Illuminate\Support\Facades\Cache::forget('dss_client_summary_v1');
            } catch (\Throwable) {}
        }
    }

    private function seed($conn): void
    {
        // Fetch top 10 clients by CLIENT_NO
        $clients = $conn->table('CLIENTS')
            ->orderBy('CLIENT_NO')
            ->limit(10)
            ->get()
            ->map(fn ($row) => (int) ($row->client_no ?? $row->CLIENT_NO))
            ->toArray();

        $empIds   = self::EMPLOYEE_IDS;
        $products = self::PRODUCTS;
        $fromTs   = strtotime(self::DATE_FROM);
        $toTs     = strtotime(self::DATE_TO);

        // Guaranteed recent date pools so every range tab has data
        // today = 2026-07-07
        $today    = strtotime('2026-07-07');
        $recentDatePools = [
            'last7'  => [$today - 6 * 86400,  $today],             // last 7 days
            'last30' => [$today - 29 * 86400,  $today - 7 * 86400], // 8–30 days ago
            'last90' => [$today - 89 * 86400,  $today - 30 * 86400],// 31–90 days ago
            'last12m'=> [$today - 364 * 86400, $today - 90 * 86400],// 91–365 days ago
        ];

        $total = 0;

        foreach ($clients as $idx => $clientNo) {

            // ── Phase 1: 6 invoices guaranteed in each recent bucket (24 total) ──
            foreach ($recentDatePools as $pool => [$poolFrom, $poolTo]) {
                for ($r = 0; $r < 6; $r++) {
                    $ts          = random_int($poolFrom, $poolTo);
                    $invoiceDate = date('Y-m-d', $ts);
                    $employeeId  = $empIds[($idx * 100 + $r) % count($empIds)];

                    $this->insertInvoice($conn, $invoiceDate, $clientNo, $employeeId, $products);
                    $total++;
                }
            }

            // ── Phase 2: remaining 26 invoices spread across full date range ──
            for ($inv = 0; $inv < 26; $inv++) {
                $ts          = random_int($fromTs, $toTs);
                $invoiceDate = date('Y-m-d', $ts);
                $employeeId  = $empIds[($idx * self::INVOICES_PER_CLIENT + $inv) % count($empIds)];

                $this->insertInvoice($conn, $invoiceDate, $clientNo, $employeeId, $products);
                $total++;
            }

            $clientNameRow = $conn->table('CLIENTS')
                ->where('CLIENT_NO', $clientNo)
                ->first();
            $clientName = $clientNameRow
                ? (string) ($clientNameRow->client_name ?? $clientNameRow->CLIENT_NAME ?? $clientNo)
                : (string) $clientNo;

            $this->command?->info("  ✓ {$clientName} ({$clientNo}) — 50 invoices inserted.");
        }

        $this->command?->info("\nDssClientSeeder: done. Total invoices inserted: {$total}");
    }

    private function insertInvoice($conn, string $invoiceDate, int $clientNo, int $employeeId, array $products): void
    {
        $conn->statement(
            "INSERT INTO \"INVOICES\" (\"INVOICE_DATE\",\"CLIENT_NO\",\"EMPLOYEE_ID\",\"INVOICE_STATUS\",\"INVOICE_MEMO\")
             VALUES (TO_DATE(?, 'YYYY-MM-DD'), ?, ?, 'Completed', ?)",
            [$invoiceDate, $clientNo, $employeeId, self::MEMO]
        );

        // Read back the Oracle-generated INVOICE_NO
        $row = $conn->selectOne('SELECT "' . self::INVOICE_SEQ . '".CURRVAL AS invoice_no FROM DUAL');
        $invoiceNo = (int) ($row->invoice_no ?? $row->INVOICE_NO ?? 0);
        if ($invoiceNo === 0) {
            $invoiceNo = (int) $conn->table('INVOICES')->max('INVOICE_NO');
        }

        // 4 distinct random products, qty 5–10 each
        $shuffled  = $products;
        shuffle($shuffled);
        $lineItems = array_slice($shuffled, 0, self::ITEMS_PER_INVOICE);

        foreach ($lineItems as [$productNo, $unitPrice]) {
            $qty = random_int(self::QTY_MIN, self::QTY_MAX);
            $conn->statement(
                "INSERT INTO \"INVOICE_DETAILS\" (\"INVOICE_NO\",\"PRODUCT_NO\",\"QTY\",\"PRICE\")
                 VALUES (?, ?, ?, ?)",
                [$invoiceNo, $productNo, $qty, $unitPrice]
            );
        }
    }
}

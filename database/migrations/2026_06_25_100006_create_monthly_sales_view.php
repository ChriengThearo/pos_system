<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW "MONTHLY_SALES" ("MONTH", "PRODUCT_NO", "PRODUCT_NAME", "SALES", "UNITS") AS
  SELECT
  TRUNC(invoice_date,'MM') AS month,
  product_no,
  product_name,
  SUM(amount) AS sales,
  SUM(qty)    AS units
FROM v_invoice_details
GROUP BY TRUNC(invoice_date,'MM'), product_no, product_name
ORDER BY month, sales DESC
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW "MONTHLY_SALES"');
    }
};

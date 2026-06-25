<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW "analyst_products" ("MONTH", "PRODUCT_NO", "PRODUCT_NAME", "SALES", "UNITS", "RNK") AS
  WITH pm AS (
  SELECT
    TRUNC(invoice_date,'MM') AS month,
    product_no,
    product_name,
    SUM(amount) AS sales,
    SUM(qty) AS units
  FROM v_invoice_details
  GROUP BY TRUNC(invoice_date,'MM'), product_no, product_name
),
ranked AS (
  SELECT pm.*,
         DENSE_RANK() OVER(PARTITION BY month ORDER BY sales DESC) rnk
  FROM pm
)
SELECT "MONTH","PRODUCT_NO","PRODUCT_NAME","SALES","UNITS","RNK"
FROM ranked
WHERE rnk <= 1
ORDER BY month, rnk
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW "analyst_products"');
    }
};

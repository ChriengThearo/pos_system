<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW "FUTURE_PRODUCTS" ("FORECAST_MONTH", "PRODUCT_NO", "PRODUCT_NAME", "FORECAST_UNITS") AS
  WITH params AS (
  SELECT
    --get the lastest data using method MAX
    --get the specific field using method extract
    MAX(EXTRACT(YEAR FROM TO_DATE(month,'DD-MON-RR'))) + 1 AS forecast_year
  FROM monthly_sales
),

-- CTE to calculate regression values per product and month
m AS (
  SELECT
    product_no,

    -- Get product name (MAX used because of GROUP BY)
    -- method on this way use simular as distinct
    MAX(product_name) AS product_name,

    -- Extract month number (1–12) from the date
    EXTRACT(MONTH FROM TO_DATE(month,'DD-MON-RR')) AS mon,

    -- Calculate slope of linear regression (trend per year)
    REGR_SLOPE(
      units,
      EXTRACT(YEAR FROM TO_DATE(month,'DD-MON-RR'))
    ) AS slope,

    -- Calculate intercept of regression line
    REGR_INTERCEPT(
      units,
      EXTRACT(YEAR FROM TO_DATE(month,'DD-MON-RR'))
    ) AS intercept,

    -- Count number of data points used in regression
    REGR_COUNT(
      units,
      EXTRACT(YEAR FROM TO_DATE(month,'DD-MON-RR'))
    ) AS n,

    -- Get the units value from the most recent year (fallback value)
    MAX(units) KEEP (
      DENSE_RANK LAST
      ORDER BY EXTRACT(YEAR FROM TO_DATE(month,'DD-MON-RR'))
    ) AS last_units

  FROM monthly_sales

  -- Group by product and month-of-year
  GROUP BY
    product_no,
    EXTRACT(MONTH FROM TO_DATE(month,'DD-MON-RR'))
)

-- Final select to generate forecast output
SELECT

  -- Build first day of each forecast month (YYYY-MM-01)
  TO_DATE(
    p.forecast_year || '-' || LPAD(m.mon,2,'0') || '-01',
    'YYYY-MM-DD'
  ) AS forecast_month,

  m.product_no,
  m.product_name,

  -- If enough data points exist, use regression formula
  -- Otherwise, use last observed units
  CASE
    WHEN m.n >= 2 THEN
      ROUND(
        GREATEST(
          0,
          m.intercept + m.slope * p.forecast_year
        )
      )
    ELSE
      m.last_units
  END AS forecast_units

FROM m

-- Attach forecast year to every row
CROSS JOIN params p

-- Order result by month and product
ORDER BY forecast_month, product_no
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW "FUTURE_PRODUCTS"');
    }
};

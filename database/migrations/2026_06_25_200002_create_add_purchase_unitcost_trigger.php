<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE TRIGGER "ADD_PURCHASE_UNITCOST" BEFORE INSERT ON PURCHASE_DETAILS
FOR EACH ROW
DECLARE
  C_PRICE PRODUCTS.COST_PRICE%TYPE;
BEGIN
  --AUTO INSERT COST_PRICE TO PRODUCT_DETAILS
  SELECT COST_PRICE INTO C_PRICE
  FROM PRODUCTS
  WHERE PRODUCT_NO=:NEW.PRODUCT_NO;
    :NEW.UNIT_COST:= C_PRICE;
END;
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER "ADD_PURCHASE_UNITCOST"');
    }
};

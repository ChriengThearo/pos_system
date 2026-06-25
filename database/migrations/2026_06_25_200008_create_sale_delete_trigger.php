<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE TRIGGER "SALE_DELETE" AFTER DELETE ON INVOICE_DETAILS
FOR EACH ROW
BEGIN
  UPDATE PRODUCTS SET QTY_ON_HAND = QTY_ON_HAND+:OLD.QTY
  WHERE PRODUCT_NO = :OLD.PRODUCT_NO;
END;
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER "SALE_DELETE"');
    }
};

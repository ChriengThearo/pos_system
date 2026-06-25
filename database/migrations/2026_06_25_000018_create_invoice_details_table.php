<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE "INVOICE_DETAILS"
   (	"INVOICE_NO" NUMBER(12,0),
	"PRODUCT_NO" VARCHAR2(20),
	"QTY" NUMBER(8,0),
	"PRICE" NUMBER(12,2),
	 CONSTRAINT "PK_INVOICE_DETAILS" PRIMARY KEY ("INVOICE_NO", "PRODUCT_NO")
  USING INDEX  ENABLE,
	 CONSTRAINT "FK_INVDET_INVOICE" FOREIGN KEY ("INVOICE_NO")
	  REFERENCES "INVOICES" ("INVOICE_NO") ENABLE,
	 CONSTRAINT "FK_INVDET_PRODUCT" FOREIGN KEY ("PRODUCT_NO")
	  REFERENCES "PRODUCTS" ("PRODUCT_NO") ENABLE
   )
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE "INVOICE_DETAILS" CASCADE CONSTRAINTS PURGE');
    }
};

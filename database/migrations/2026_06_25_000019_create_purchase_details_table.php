<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE "PURCHASE_DETAILS"
   (	"PURCHASE_NO" NUMBER NOT NULL ENABLE,
	"PRODUCT_NO" VARCHAR2(20) NOT NULL ENABLE,
	"QUANTITY" NUMBER(5,0),
	"UNIT_COST" NUMBER(5,0),
	 CONSTRAINT "PURCHASE_DETAILS_PK" PRIMARY KEY ("PRODUCT_NO", "PURCHASE_NO")
  USING INDEX  ENABLE,
	 CONSTRAINT "PURCHASE_DETAILS_FK1" FOREIGN KEY ("PURCHASE_NO")
	  REFERENCES "PURCHASES" ("PURCHASE_NO") ENABLE,
	 CONSTRAINT "PURCHASE_DETAILS_FK2" FOREIGN KEY ("PRODUCT_NO")
	  REFERENCES "PRODUCTS" ("PRODUCT_NO") ENABLE
   )
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE "PURCHASE_DETAILS" CASCADE CONSTRAINTS PURGE');
    }
};

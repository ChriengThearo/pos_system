<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW "V_INVOICE_DETAILS" ("INVOICE_NO", "INVOICE_DATE", "PRODUCT_NO", "PRODUCT_NAME", "QTY", "PRICE", "AMOUNT") AS
  Select i.INVOICE_NO, i.INVOICE_DATE, ivd.PRODUCT_NO,p.product_name,ivd.QTY,ivd.PRICE,
        ivd.QTY*ivd.PRICE AS AMOUNT
From clients c inner join invoices i on c.client_no=i.client_no
      inner join employees e on e.employee_id=i.employee_id
      inner join invoice_details ivd on i.invoice_no=ivd.invoice_no
      inner join products p on p.product_no=ivd.product_no
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW "V_INVOICE_DETAILS"');
    }
};

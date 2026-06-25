<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW "TOTALAMOUNTBYINVOICENO" ("INVOICE_NO", "INVOICE_DATE", "SELLER", "CLIENT_NO", "CLIENT_NAME", "Item No", "TOTAL", "DISCOUNT", "BALANCE", "INVOICE_STATUS") AS
  select invoice_no, INVOICE_DATE, EMPLOYEE_NAME as seller, client_no, CLIENT_NAME,count(*) as "Item No",sum("TOTAL LINE") as Total, DISCOUNT, sum(BALANCE) as balance, invoice_status
from totalsaledetail
group by invoice_no, INVOICE_DATE, EMPLOYEE_NAME, client_no, CLIENT_NAME,discount, invoice_status
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW "TOTALAMOUNTBYINVOICENO"');
    }
};

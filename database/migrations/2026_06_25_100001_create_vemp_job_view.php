<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW "VEMP_JOB" ("EMPLOYEE_ID", "EMPLOYEE_NAME", "GENDER", "BIRTH_DATE", "JOB_ID", "JOB_TITLE", "ADDRESS", "PHONE", "SALARY", "REMARKS") AS
  select e.EMPLOYEE_ID, e.EMPLOYEE_NAME, e.GENDER, e.BIRTH_DATE,
        j.JOB_ID, j.JOB_TITLE, e.ADDRESS, e.PHONE, e.SALARY, e.REMARKS
from jobs j inner join employees e on j.job_id=e.job_id
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW "VEMP_JOB"');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE TRIGGER "CHECK_SALARY"
before insert or update of salary, job_id on employees
for each row
declare
vmin_sal jobs.min_salary%type;
vmax_sal jobs.max_salary%type;
begin
  select min_salary,max_salary into vmin_sal, vmax_sal
  from jobs where job_id=:new.job_id;
   if (:new.salary<vmin_sal or :new.salary>vmax_sal) then
   rollback;
  end if;

end;
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER "CHECK_SALARY"');
    }
};

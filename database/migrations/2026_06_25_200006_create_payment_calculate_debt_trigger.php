<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE TRIGGER "PAYMENT_CALCULATE_DEBT"
BEFORE INSERT OR UPDATE ON PAYMENTS
FOR EACH ROW
BEGIN
    :NEW.DEBT_AMOUNT := :NEW.AMOUNT - :NEW.RECIEVE_AMOUNT;

      IF :NEW.DEBT_AMOUNT < 0 THEN
          :NEW.DEBT_AMOUNT := 0;
    END IF;
END;
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER "PAYMENT_CALCULATE_DEBT"');
    }
};

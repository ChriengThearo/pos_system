<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE TRIGGER "PAYMENTS_CALCULATE_USD"
BEFORE INSERT OR UPDATE ON PAYMENTS
FOR EACH ROW
DECLARE
    v_rate CURRENCIES.EXCHANGE_RATE_TO_USD%TYPE;
BEGIN
    SELECT EXCHANGE_RATE_TO_USD
    INTO v_rate
    FROM CURRENCIES
    WHERE CURRENCY_NO = :NEW.CURRENCY_NO;

    :NEW.USD := :NEW.AMOUNT / v_rate;
END;
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER "PAYMENTS_CALCULATE_USD"');
    }
};

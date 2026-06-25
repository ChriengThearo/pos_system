<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE TRIGGER "ALERT_STOCKS"
BEFORE INSERT OR UPDATE OF qty_on_hand ON products
FOR EACH ROW
DECLARE
  v_lower_qty  alert_stocks.lower_qty%TYPE;
  v_higher_qty alert_stocks.higher_qty%TYPE;
BEGIN
  SELECT lower_qty, higher_qty
    INTO v_lower_qty, v_higher_qty
    FROM alert_stocks
   WHERE product_no = :NEW.product_no;

  IF :NEW.qty_on_hand < v_lower_qty THEN
    :NEW.status := 'Understock';
  ELSIF :NEW.qty_on_hand > v_higher_qty THEN
    :NEW.status := 'Overstock';
  ELSE
    :NEW.status := 'Enough';
  END IF;

EXCEPTION
  WHEN NO_DATA_FOUND THEN
    :NEW.status := NULL; -- or 'No Threshold'
END;
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER "ALERT_STOCKS"');
    }
};

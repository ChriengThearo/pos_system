<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE TRIGGER "SALE_ADD"
BEFORE INSERT ON INVOICE_DETAILS
FOR EACH ROW
DECLARE
    v_price PRODUCTS.SELL_PRICE%TYPE;
    v_stock PRODUCTS.QTY_ON_HAND%TYPE;
BEGIN
    SELECT SELL_PRICE, QTY_ON_HAND
      INTO v_price, v_stock
      FROM PRODUCTS
     WHERE PRODUCT_NO = :NEW.PRODUCT_NO
       FOR UPDATE;

    IF NVL(:NEW.QTY, 0) <= 0 THEN
        RAISE_APPLICATION_ERROR(-20003, 'QTY must be greater than 0.');
    END IF;

    IF NVL(:NEW.QTY, 0) > NVL(v_stock, 0) THEN
        RAISE_APPLICATION_ERROR(
            -20001,
            'Only ' || TO_CHAR(NVL(v_stock, 0)) || ' unit(s) are available for product ' || :NEW.PRODUCT_NO || '. Please reduce QTY.'
        );
    END IF;

    UPDATE PRODUCTS
       SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) - :NEW.QTY
     WHERE PRODUCT_NO = :NEW.PRODUCT_NO;

    :NEW.PRICE := v_price;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20002, 'Product ' || :NEW.PRODUCT_NO || ' was not found.');
END;
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER "SALE_ADD"');
    }
};

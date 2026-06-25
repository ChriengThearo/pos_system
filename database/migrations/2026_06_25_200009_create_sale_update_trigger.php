<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE TRIGGER "SALE_UPDATE"
BEFORE UPDATE ON INVOICE_DETAILS
FOR EACH ROW
DECLARE
    v_price PRODUCTS.SELL_PRICE%TYPE;
    v_stock PRODUCTS.QTY_ON_HAND%TYPE;
    v_old_stock PRODUCTS.QTY_ON_HAND%TYPE;
    v_effective_available NUMBER;
BEGIN
    IF NVL(:NEW.QTY, 0) <= 0 THEN
        RAISE_APPLICATION_ERROR(-20003, 'QTY must be greater than 0.');
    END IF;

    IF :OLD.PRODUCT_NO = :NEW.PRODUCT_NO THEN
        SELECT SELL_PRICE, QTY_ON_HAND
          INTO v_price, v_stock
          FROM PRODUCTS
         WHERE PRODUCT_NO = :NEW.PRODUCT_NO
           FOR UPDATE;

        v_effective_available := NVL(v_stock, 0) + NVL(:OLD.QTY, 0);
        IF NVL(:NEW.QTY, 0) > v_effective_available THEN
            RAISE_APPLICATION_ERROR(
                -20001,
                'Only ' || TO_CHAR(v_effective_available) || ' unit(s) are available for product ' || :NEW.PRODUCT_NO || '. Please reduce QTY.'
            );
        END IF;

        UPDATE PRODUCTS
           SET QTY_ON_HAND = v_effective_available - :NEW.QTY
         WHERE PRODUCT_NO = :NEW.PRODUCT_NO;
    ELSE
        SELECT QTY_ON_HAND
          INTO v_old_stock
          FROM PRODUCTS
         WHERE PRODUCT_NO = :OLD.PRODUCT_NO
           FOR UPDATE;

        UPDATE PRODUCTS
           SET QTY_ON_HAND = NVL(v_old_stock, 0) + NVL(:OLD.QTY, 0)
         WHERE PRODUCT_NO = :OLD.PRODUCT_NO;

        SELECT SELL_PRICE, QTY_ON_HAND
          INTO v_price, v_stock
          FROM PRODUCTS
         WHERE PRODUCT_NO = :NEW.PRODUCT_NO
           FOR UPDATE;

        IF NVL(:NEW.QTY, 0) > NVL(v_stock, 0) THEN
            RAISE_APPLICATION_ERROR(
                -20001,
                'Only ' || TO_CHAR(NVL(v_stock, 0)) || ' unit(s) are available for product ' || :NEW.PRODUCT_NO || '. Please reduce QTY.'
            );
        END IF;

        UPDATE PRODUCTS
           SET QTY_ON_HAND = NVL(v_stock, 0) - :NEW.QTY
         WHERE PRODUCT_NO = :NEW.PRODUCT_NO;
    END IF;

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
        DB::unprepared('DROP TRIGGER "SALE_UPDATE"');
    }
};

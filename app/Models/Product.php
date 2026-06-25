<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'PRODUCTS';
    protected $connection = 'oracle';
    protected $primaryKey = 'PRODUCT_NO';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['PRODUCT_NO', 'PRODUCT_NAME', 'PRODUCT_TYPE', 'PROFIT_PERCENT', 'UNIT_MEASURE', 'SELL_PRICE', 'COST_PRICE', 'QTY_ON_HAND', 'STATUS'];
}

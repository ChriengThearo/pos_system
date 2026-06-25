<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
{
    protected $table = 'PURCHASE_DETAILS';
    protected $connection = 'oracle';
    protected $primaryKey = null; // composite key: PRODUCT_NO, PURCHASE_NO
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['PURCHASE_NO', 'PRODUCT_NO', 'QUANTITY', 'UNIT_COST'];
}

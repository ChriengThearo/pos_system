<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertStock extends Model
{
    protected $table = 'ALERT_STOCKS';
    protected $connection = 'oracle';
    protected $primaryKey = 'ALERT_STOCK_NO';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['ALERT_STOCK_NO', 'PRODUCT_NO', 'LOWER_QTY', 'HIGHER_QTY'];
}

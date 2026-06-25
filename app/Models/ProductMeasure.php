<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMeasure extends Model
{
    protected $table = 'PRODUCT_MEASURE';
    protected $connection = 'oracle';
    protected $primaryKey = 'MEASURE_ID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['MEASURE_ID', 'MEASURE_NAME'];
}

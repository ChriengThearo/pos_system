<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    protected $table = 'PRODUCT_TYPE';
    protected $connection = 'oracle';
    protected $primaryKey = 'PRODUCTTYPE_ID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['PRODUCTTYPE_ID', 'PRODUCTYPE_NAME', 'REMARKS'];
}

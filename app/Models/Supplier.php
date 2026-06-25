<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'SUPPLIERS';
    protected $connection = 'oracle';
    protected $primaryKey = 'SUPPLIER_ID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['SUPPLIER_ID', 'SUPPLIER_NAME', 'ADDRESS', 'COUNTRY_CITY', 'PHONE', 'EMAIL'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'PURCHASES';
    protected $connection = 'oracle';
    protected $primaryKey = 'PURCHASE_NO';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['PURCHASE_NO', 'PURCHASE_DATE', 'SUPLIER_ID', 'EMPLOYEE_ID', 'PURCHASE_MEMO'];
}

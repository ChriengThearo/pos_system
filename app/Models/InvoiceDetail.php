<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    protected $table = 'INVOICE_DETAILS';
    protected $connection = 'oracle';
    protected $primaryKey = null; // composite key: INVOICE_NO, PRODUCT_NO
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['INVOICE_NO', 'PRODUCT_NO', 'QTY', 'PRICE'];
}

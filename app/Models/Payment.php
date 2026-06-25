<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'PAYMENTS';
    protected $connection = 'oracle';
    protected $primaryKey = 'PAYMENT_NO';
    public $incrementing = false;
    public $timestamps = true;
    const CREATED_AT = 'CREATE_AT';
    const UPDATED_AT = 'UPDATE_AT';

    protected $fillable = ['PAYMENT_NO', 'INVOICE_NO', 'PAYMENT_METHOD', 'AMOUNT', 'CURRENCY_NO', 'USD', 'CREATE_AT', 'UPDATE_AT', 'RECIEVE_AMOUNT', 'DEBT_AMOUNT'];
}

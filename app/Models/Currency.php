<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = 'CURRENCIES';
    protected $connection = 'oracle';
    protected $primaryKey = 'CURRENCY_NO';
    public $incrementing = false;
    public $timestamps = true;
    const CREATED_AT = 'CREATE_AT';
    const UPDATED_AT = 'UPDATE_AT';

    protected $fillable = ['CURRENCY_NO', 'CURRENCY_NAME', 'SYMBOL', 'EXCHANGE_RATE_TO_USD', 'CREATE_AT', 'UPDATE_AT'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'INVOICES';
    protected $connection = 'oracle';
    protected $primaryKey = 'INVOICE_NO';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['INVOICE_NO', 'INVOICE_DATE', 'CLIENT_NO', 'EMPLOYEE_ID', 'INVOICE_STATUS', 'INVOICE_MEMO'];
}

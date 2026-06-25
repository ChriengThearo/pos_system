<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientType extends Model
{
    protected $table = 'CLIENT_TYPE';
    protected $connection = 'oracle';
    protected $primaryKey = 'CLIENTTYPE_ID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['CLIENTTYPE_ID', 'TYPE_NAME', 'DISCOUNT_RATE', 'REMARKS'];
}

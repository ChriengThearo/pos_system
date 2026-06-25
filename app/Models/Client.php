<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'CLIENTS';
    protected $connection = 'oracle';
    protected $primaryKey = 'CLIENT_NO';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['CLIENT_NO', 'CLIENT_NAME', 'ADDRESS', 'CITY', 'PHONE', 'CLIENT_TYPE', 'DISCOUNT'];
}

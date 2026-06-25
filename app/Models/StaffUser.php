<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffUser extends Model
{
    protected $table = 'USERS';
    protected $connection = 'oracle';
    protected $primaryKey = null; // composite key: G_ID, E_ID
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['USER_ID', 'E_ID', 'G_ID', 'PASSWORD', 'CREATE_DATE', 'STATUS'];
}

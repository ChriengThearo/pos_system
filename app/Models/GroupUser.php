<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupUser extends Model
{
    protected $table = 'GROUP_USER';
    protected $connection = 'oracle';
    protected $primaryKey = 'G_ID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['G_ID', 'GRUOP_NAME', 'GROUP_STATUS', 'CREATE_DATE'];
}

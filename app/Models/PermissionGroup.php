<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionGroup extends Model
{
    protected $table = 'PERMISSION_GROUP';
    protected $connection = 'oracle';
    protected $primaryKey = null; // composite key: G_ID, FORM_ID
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['PERMISSION_ID', 'G_ID', 'FORM_ID', 'GRANT_DATE'];
}

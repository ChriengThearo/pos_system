<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormContol extends Model
{
    protected $table = 'FORM_CONTOL';
    protected $connection = 'oracle';
    protected $primaryKey = 'FORM_ID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['FORM_ID', 'FORM_NAME', 'FORM_TITLE', 'CREATE_DATE', 'FORM_STATUS'];
}

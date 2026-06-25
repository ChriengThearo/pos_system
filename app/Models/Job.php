<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'JOBS';
    protected $connection = 'oracle';
    protected $primaryKey = 'JOB_ID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['JOB_ID', 'JOB_TITLE', 'MIN_SALARY', 'MAX_SALARY'];
}

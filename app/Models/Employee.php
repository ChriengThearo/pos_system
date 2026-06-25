<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'EMPLOYEES';
    protected $connection = 'oracle';
    protected $primaryKey = 'EMPLOYEE_ID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['EMPLOYEE_ID', 'EMPLOYEE_NAME', 'GENDER', 'BIRTH_DATE', 'JOB_ID', 'ADDRESS', 'PHONE', 'SALARY', 'REMARKS', 'PHOTO'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPhoto extends Model
{
    protected $table = 'PRODUCT_PHOTO';
    protected $connection = 'oracle';
    protected $primaryKey = 'PHOTO_ID';
    public $incrementing = false;
    public $timestamps = true;
    const CREATED_AT = 'CREATED_AT';
    const UPDATED_AT = 'UPDATED_AT';

    protected $fillable = ['PHOTO_ID', 'PRODUCT_ID', 'MEDIA', 'CREATED_AT', 'UPDATED_AT'];
}

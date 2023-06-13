<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegisterArea extends Model
{
    protected $table = 'register_areas';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_phone',
        'user_code',
        'city_code',
        'city_name',
        'district_code',
        'district_name',
        'ward_code',
        'ward_name',
        'store_id',
        'company_id'
    ];
}

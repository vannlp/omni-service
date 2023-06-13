<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CheckActiveAccesstrade extends Model
{
    protected $table = 'check_active_accesstrade';

    protected $fillable = [
        'code',
        'is_active',
    ];
}

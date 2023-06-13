<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApiIp extends Model
{
    protected $table = 'api_ips';

    protected $fillable
        = [
            'ip',
            'count_failed',
            'updated_at',
            'created_at'
        ];
}

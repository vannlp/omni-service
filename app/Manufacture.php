<?php

namespace App;

class Manufacture extends BaseModel
{
    protected $table = 'manufactures';

    protected $fillable = [
        'code',
        'name',
        'company_id',
        'store_id',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];
}

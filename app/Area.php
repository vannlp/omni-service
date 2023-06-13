<?php

/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:43 AM
 */

namespace App;


class Area extends BaseModel
{
    protected $table = 'areas';

    protected $fillable = [
        'code',
        'name',
        'description',
        'company_id',
        'image_id',
        'store_id',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'image_id');
    }
}

<?php

namespace App;

class Zalo extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'zalo';

    /**
     * @var array
     */
    protected $fillable = [
        'zalo_access_token',
        'zalo_oaid',
        'store_id',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
        'deleted_at',
        'deleted_by'
    ];
}

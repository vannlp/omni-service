<?php

namespace App;

class Channel extends BaseModel
{
    protected $table = 'channel_types';

    protected $fillable
        = [
            'code',
            'name',
            'parent_channel_type_id',
            'status',
            'type',
            'sku',
            'sale_amount',
            'object_type',
            'deleted',
            'deleted_at',
            'deleted_by',
            'updated_by',
            'created_by',
            'updated_at',
            'created_at',
        ];

}

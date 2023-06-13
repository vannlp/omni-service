<?php

namespace App;

class Routing extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'routings';


    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'routing_id',
        'routing_code',
        'company_id',
        'store_id',
        'routing_name',
        'shop_id',
        'status',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];
}

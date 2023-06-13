<?php

namespace App;


class ShippingHistoryStatus extends BaseModel
{
    protected $table = 'shipping_histories_status';

    protected $fillable = [
        'shipping_id',
        'status_code',
        'text_status_code',
        'phone_driver',
        'name_driver',
        'license_plate',
        'company_id',
        'is_active',
        'log_shipping',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];
}
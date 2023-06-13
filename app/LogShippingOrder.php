<?php

namespace App;

class LogShippingOrder extends BaseModel
{
    protected $table = 'log_shipping_order';

    protected $fillable
        = [
            'order_code',
            'type',
            'code_shipping_method',
            'reponse_json',
            'message',
            'param_request',
            'deleted',
            'updated_by',
            'created_by',
            'updated_at',
            'created_at',
        ];

}

<?php

namespace App;

class LogPaymentRequest extends BaseModel
{
    protected $table = 'log_payment_request';

    protected $fillable
        = [
            'order_code',
            'reponse_json',
            'type',
            'deleted',
            'updated_by',
            'created_by',
            'updated_at',
            'created_at',
        ];

}

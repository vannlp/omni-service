<?php


namespace App;


class PaymentRefund extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'payment_refunds';
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'order_id',
        'code_refund',
        'code_request',
        'type',
        'method',
        'trading_code',
        'price_refund',
        'data',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'upadted_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

}
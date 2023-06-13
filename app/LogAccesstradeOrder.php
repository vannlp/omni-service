<?php

namespace App;

class LogAccesstradeOrder extends BaseModel
{
    protected $table = 'log_accesstrade_order';

    protected $fillable = [
        'order_id',
        'click_id',
        'campaign_id',
        'conversion_id',
        'data',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}

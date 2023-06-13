<?php


namespace App;


class PaymentLogFail extends BaseModel
{
    protected $table = 'payment_log_fail';
    protected $fillable = [
        "order_id",
        "type",
        "log",
        "is_active",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];
}
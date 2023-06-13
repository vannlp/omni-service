<?php


namespace App;


class CouponProductexcept extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'coupon_products_except';
    /**
     * @var string[]
     */
    protected $fillable = [
        'coupon_id',
        'product_id',
        'product_code',
        'product_name',
        'product_name',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];
}
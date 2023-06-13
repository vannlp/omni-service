<?php


namespace App;


class CouponCategoryexcept extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'coupon_categories_except';
    /**
     * @var string[]
     */
    protected $fillable = [
        'coupon_id',
        'category_id',
        'category_code',
        'category_name',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];
}
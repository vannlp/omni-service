<?php


namespace App;


class CouponCodes extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'coupon_codes';
    /**
     * @var string[]
     */
    protected $fillable = [
        'coupon_id',
        'code',
        'is_active',
        'type',
        'discount',
        'limit_discount',
        'user_code',
        'start_date',
        'end_date',
        'order_used',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];
    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'order_used');
    }
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_code');
    }
    public function coupon()
    {
        return $this->hasOne(Coupon::class, 'id', 'coupon_id');
    }
}

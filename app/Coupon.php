<?php


namespace App;


class Coupon extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'coupons';
    /**
     * @var string[]
     */
    protected $fillable
    = [
        'code',
        'name',
        'type',
        'content',
        'type_discount',
        'type_apply',
        'apply_discount',
        'stack_able',
        'total',
        'condition',
        'mintotal',
        'maxtotal',
        'free_shipping',
        'product_ids',
        'product_codes',
        'product_names',
        'category_ids',
        'category_codes',
        'category_names',
        'product_except_ids',
        'product_except_codes',
        'product_except_names',
        'category_except_ids',
        'category_except_codes',
        'category_except_names',
        'date_start',
        'date_end',
        'uses_total',
        'uses_customer',
        'status',
        'company_id',
        'store_id',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'coupon_code',
        'coupon_name',
        'coupon_price',
        'thumbnail',
        'thumbnail_id'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'coupon_code', 'code');
    }

    public function history()
    {
        return $this->hasMany(CouponHistory::class, 'coupon_code', 'code');
    }

    public function ordersDelivery()
    {
        return $this->hasMany(Order::class, 'coupon_delivery_code', 'code');
    }

    public function getTotalUsed($customer = null)
    {
        $orders = $this->orders;

        if ($customer) {
            $orders = $orders->where('customer_id', $customer->id)->where('status', '!=', 'CANCELED');
        }

        return $orders->count();
    }

    public function getTotalUsedDelivery($customer = null)
    {
        $orders = $this->ordersDelivery;

        if ($customer) {
            $orders = $orders->where('customer_id', $customer->id)->where('status', '!=', 'CANCELED');
        }

        return $orders->count();
    }

    public function coupon()
    {
        return $this->hasMany(CouponCodes::class, 'coupon_id', 'id');
    }

    public function getClientTotalUsed($sessions = null)
    {
        $orders = $this->orders;

        if ($sessions) {
            $orders = $orders->where('session_id', $sessions->session_id);
        }

        return $orders->count();
    }

    public function couponCategories()
    {
        return $this->hasMany(CouponCategory::class, 'coupon_id', 'id');
    }

    public function couponProducts()
    {
        return $this->hasMany(CouponProduct::class, 'coupon_id', 'id');
    }

    public function couponCategoriesexcept()
    {
        return $this->hasMany(CouponCategoryexcept::class, 'coupon_id', 'id');
    }

    public function couponProductsexcept()
    {
        return $this->hasMany(CouponProductexcept::class, 'coupon_id', 'id');
    }
}

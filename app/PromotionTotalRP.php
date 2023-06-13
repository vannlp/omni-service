<?php
/**
 * User: dai.ho
 * Date: 1/07/2020
 * Time: 3:26 PM
 */

namespace App;


class PromotionTotalRP extends BaseModel
{
    public static $current;

    protected $table = 'promotion_totals';

    protected $connection = 'mysql2';

    protected $fillable = [
        'id',
        'cart_id',
        'order_id',
        'order_code',
        'order_customer_id',
        'order_customer_code',
        'order_customer_name',
        'promotion_id',
        'promotion_code',
        'promotion_name',
        'promotion_type',
        'value',
        'promotion_act_approval',
        'promotion_act_type',
        'promotion_act_price',
        'promotion_act_sale_type',
        'promotion_info',
        'company_id',
        'store_id',
        'approval_status',
        'approved_at',
        'approved_by',
        'approved_at',
        'approved_by',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }

    public function customer()
    {
        return $this->hasOne(User::class, 'id', 'order_customer_id');
    }
}
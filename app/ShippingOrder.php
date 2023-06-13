<?php
/**
 * User: dai.ho
 * Date: 29/06/2020
 * Time: 1:13 PM
 */

namespace App;


class ShippingOrder extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shipping_orders';

    protected $fillable = [
        'ship_code',
        'type',
        'code',
        'name',
        'code_type_ghn',
        'status',
        'status_shipping_method',
        'status_text',
        'tracking_url',
        'delivery_status',
        'param_push_shipping',
        'count_push_shipping',
        'ship_fee',
        'pick_money',
        'pick_money',
        'transport',
        'estimated_pick_time',
        'estimated_deliver_time',
        'result_json',
        'reason',
        'shipping_order_cool',
        'count_print',
        'description',
        'order_id',
        'company_id',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }

    public function details()
    {
        return $this->hasMany(ShippingOrderDetail::class, 'shipping_order_id', 'id');
    }
}

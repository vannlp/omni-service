<?php
/**
 * User: dai.ho
 * Date: 3/06/2020
 * Time: 10:10 AM
 */

namespace App;


class EnterpriseOrderDetail extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'enterprise_order_details';

    protected $fillable = [
        'enterprise_order_id',
        'order_detail_id',
        'product_id',
        'qty',
        'price',
        'price_down',
        'real_price',
        'total',
        'note',
        'status',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
    ];

    public function enterpriseOrder()
    {
        return $this->hasOne(EnterpriseOrder::class, 'id', 'enterprise_order_id');
    }

    public function orderDetail()
    {
        return $this->hasOne(OrderDetail::class, 'id', 'order_id');
    }

    public function product()
    {
        return $this->hasOne(__NAMESPACE__ . '\Product', 'id', 'product_id');
    }
}

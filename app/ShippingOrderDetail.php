<?php
/**
 * User: kpistech2
 * Date: 2020-07-06
 * Time: 22:16
 */

namespace App;


class ShippingOrderDetail extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shipping_order_details';

    protected $fillable = [
        'shipping_order_id',
        'product_id',
        'product_code',
        'product_name',
        'unit_id',
        'unit_code',
        'unit_name',
        'warehouse_id',
        'warehouse_code',
        'warehouse_name',
        'batch_id',
        'batch_code',
        'batch_name',
        'qty',
        'ship_qty',
        'waiting_qty',
        'shipped_qty',
        'price',
        'total_price',
        'discount',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
    ];

    public function shippingOrder()
    {
        return $this->hasOne(ShippingOrder::class, 'id', 'shipping_order_id');
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function unit()
    {
        return $this->hasOne(Unit::class, 'id', 'unit_id');
    }

    public function warehouse()
    {
        return $this->hasOne(Warehouse::class, 'id', 'warehouse_id');
    }

    public function batch()
    {
        return $this->hasOne(Batch::class, 'id', 'batch_id');
    }
}

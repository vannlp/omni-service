<?php


namespace App;


class ShipOrderDetail extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ship_order_details';

    protected $fillable = [
        'ship_id',
        'order_detail_id',
        'company_id',
        'product_id',
        'product_code',
        'product_name',
        'warehouse_id',
        'warehouse_code',
        'warehouse_name',
        'batch_id',
        'batch_code',
        'batch_name',
        'product_unit',
        'product_unit_name',
        'store_id',
        'count_qty_ship',
        'sum_qty_product_shipped',
        'available_qty',
        'ship_qty',
        'shipped_qty',
        'qty',
        'price',
        'is_active',
        'discount',
        'total',
        'item_id',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
    ];
}
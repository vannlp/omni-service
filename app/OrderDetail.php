<?php
/**
 * User: Administrator
 * Date: 21/12/2018
 * Time: 09:25 PM
 */

namespace App;


class OrderDetail extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_details';

    protected $fillable = [
        'id',
        'order_id',
        'product_id',
        'product_code',
        'product_name',
        'product_category',
        'qty',
        'qty_sale',
        'shipped_qty',
        'waiting_qty',
        'save_qty',
        'price',
        'price_down',
        'real_price',
        'total',
        'note',
        'status',
        'discount',
        'special_percentage',
        'partner_ship_fee',
        'partner_revenue_total',
        'partner_revenue_rate',
        'shine_revenue_total',
        'shine_revenue_rate',
        'commented',
        'combo_price_from',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
        'item_value',
        'item_type'
    ];

    public function order()
    {
        return $this->hasOne(__NAMESPACE__ . '\Order', 'id', 'order_id');
    }

    public function product()
    {
        return $this->hasOne(__NAMESPACE__ . '\Product', 'id', 'product_id');
    }
    public function productCombo()
    {
        return $this->hasOne(__NAMESPACE__ . '\Product', 'code', 'combo_code_from');
    }
    public function orderStatus()
    {
        return $this->hasMany(__NAMESPACE__ . '\OrderStatusHistory', 'order_id', 'order_id');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }
}

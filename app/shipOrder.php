<?php


namespace App;


class shipOrder extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ship_orders';

    protected $fillable = [
        'id',
        'code',
        'order_code',
        'order_id',
        'status',
        'company_id',
        'store_id',
        'customer_id',
        'customer_name',
        'customer_code',
        'customer_email',
        'customer_phone',
        'created_date',
        'approver',
        'approver_name',
        'qty_equal',
        'count_qty_ship',
        'description',
        'shipping_address',
        'real_date',
        'total_price',
        'qty_equal_shipped_order',
        'payment_method',
        'payment_method_name',
        'status_name',
        'payment_status_name',
        'payment_status',
        'shipping_address_full_name',
        'shipping_address_phone',
        'street_address',
        'shipping_address_city_code',
        'shipping_address_city',
        'shipping_address_district_code',
        'shipping_address_district',
        'shipping_address_ward_code',
        'shipping_address_ward',
        'exp',
        'mfg',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
    ];

    public function seller()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'seller_id');
    }

    public function order()
    {
        return $this->hasOne(__NAMESPACE__ . '\Order', 'id', 'order_id');
    }

    public function customer()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'customer_id');
    }

    public function receiver()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'receiver_id');
    }

    public function details()
    {
        return $this->hasMany(__NAMESPACE__ . '\ShipOrderDetail', 'ship_id', 'id');
    }

    public function history()
    {
        return $this->hasMany(__NAMESPACE__ . '\OrderHistory', 'order_id', 'id');
    }

    public function price()
    {
        return $this->hasOne(__NAMESPACE__ . '\Price', 'id', 'price_id');
    }

    public function user()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'approver');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function transportMasterData()
    {
        return $this->hasOne(__NAMESPACE__ . '\MasterData', 'id', 'transport');
    }
}
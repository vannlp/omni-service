<?php
/**
 * User: dai.ho
 * Date: 3/06/2020
 * Time: 10:09 AM
 */

namespace App;


class EnterpriseOrder extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'enterprise_orders';

    protected $fillable = [
        'order_id',
        'code',
        'status',
        'enterprise_id',
        'updated_date',
        'created_date',
        'completed_date',
        'canceled_date',
        'canceled_by',
        'canceled_reason',
        'latlong',
        'lat',
        'long',
        'denied_ids',
        'note',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
        'deleted',
    ];

    public function details()
    {
        return $this->hasMany(EnterpriseOrderDetail::class, 'enterprise_order_id', 'id');
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }

    public function enterprise()
    {
        return $this->hasOne(User::class, 'id', 'enterprise_id');
    }
}

<?php
/**
 * User: kpistech2
 * Date: 2020-11-06
 * Time: 10:16
 */

namespace App;


class OrderStatusHistory extends BaseModel
{
    protected $table = 'order_status_histories';

    protected $fillable = [
        'order_id',
        'order_status_id',
        'order_status_code',
        'order_status_name',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by')
            ->addSelect(['id', 'name', 'email', 'phone', 'code']);
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by')
            ->addSelect(['id', 'name', 'email', 'phone', 'code']);
    }

    public function orderStatus()
    {
        return $this->hasOne(OrderStatus::class, 'id', 'order_status_id');
    }
}
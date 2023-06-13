<?php
/**
 * User: kpistech2
 * Date: 2020-07-02
 * Time: 22:22
 */

namespace App;


class PaymentControlOrder extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_control_orders';

    protected $fillable = [
        'order_id',
        'order_code',
        'order_price',
        'payment_price',
        'price_diff',
        'control_date',
        'payment_type',
        'account_number',
        'account_name',
        'payment_date',
        'store_id',
        'company_id',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
    ];

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
}

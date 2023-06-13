<?php


namespace App;


class PaymentVirtualAccount extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_virtual_accounts';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'type',
        'order_id',
        'type_cart',
        'virtual_account_number',
        'master_account_number',
        'collect_ammount',
        'payer_name',
        'transaction_date',
        'value_date',
        'transaction_id',
        'transaction_description',
        'deleted',
        'created_at',
        'created_by',
        'upadted_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

  
}
<?php


namespace App;


class UserCustomer extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_customers';

    /**
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'shop_id',
        'code',
        'short_code',
        'first_code',
        'name',
        'contact_name',
        'phone',
        'mobiphone',
        'email',
        'channel_type_id',
        'max_debit_amount',
        'max_debit_date',
        'area_id',
        'house_number',
        'street',
        'address',
        'region',
        'image_url',
        'last_approve_order',
        'last_order',
        'status',
        'invoice_company_name',
        'invoice_company_email',
        'invoice_outlet_name',
        'invoice_tax',
        'invoice_payment_type',
        'invoice_number_account',
        'invoice_name_bank',
        'delivery_address',
        'lat',
        'name_text',
        'apply_debit_limited',
        'idno',
        'lng',
        'fax',
        'birthday',
        'frequency',
        'invoice_name_branch_bank',
        'bank_account_owner',
        'sale_position_id',
        'sale_status',
        'order_view',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by'
    ];

}
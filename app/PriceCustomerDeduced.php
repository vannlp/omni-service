<?php


namespace App;


class PriceCustomerDeduced extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'price_customer_deduced_dms_imports';

    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'product_id',
        'customer_id',
        'shop_id',
        'from_date',
        'to_date',
        'price',
        'price_not_vat',
        'package_price',
        'package_price_not_vat',
        'vat',
        'price_id',
        'status',
        'create_date',
        'update_date',
        'created_by',
        'updated_by',
        'deleted_at',
        'deleted_by',
        'deleted',
        'created_at',
        'updated_at',
    ];

    // public function product()
    // {
    //     return $this->belongsTo(Product::class, 'product_id', 'id');
    // }

    // public function price()
    // {
    //     return $this->belongsTo(Price::class, 'price_id', 'id');
    // }
}
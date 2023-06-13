<?php


namespace App;


class PriceInfo extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'price_info_dms_imports';

    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'price_id',
        'product_id',
        'customer_id',
        'customer_type_id',
        'shop_id',
        'shop_type_id',
        'from_date',
        'to_date',
        'status',
        'price',
        'price_not_vat',
        'package_price',
        'package_price_not_vat',
        'vat',
        'created_at',
        'updated_at',
        'created_user',
        'updated_user',
        'created_by',
        'updated_by',
        'deleted_at',
        'deleted_by',
        'deleted'
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
<?php


namespace App;


class CartDetail extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'cart_details';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'cart_id',
        'product_id',
        'product_code',
        'product_name',
        'product_description',
        'product_category',
        'product_thumb',
        'coupon_apply',
        'weight',
        'length',
        'width',
        'quantity',
        'options',
        'price',
        'old_product_price',
        'promotion_info',
        'promotion_price',
        'special_percentage',
        'note',
        'qty_sale_re',
        'qty_not_sale',
        'total',
        'item_value',
        'item_type',
        'discount_admin_value',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    protected $casts = [
        'promotion_info' => 'json',
        'options'        => 'json'
    ];

    public function getProduct()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'product_thumb');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
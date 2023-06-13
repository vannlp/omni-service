<?php
/*
 *
 */

namespace App;


class PromotionDetail extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'promotion_details';

    protected $fillable = [
        'promotion_id',
        'category_id',
        'product_id',
        'qty',
        'qty_gift',
        'qty_from',
        'qty_to',
        'point',
        'price',
        'price_gift',
        'sale_off',
        'gift_product_id',
        'discount',
        'customer_type',
        'note',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
        'deleted',
    ];

    public function promotion()
    {
        return $this->hasOne(__NAMESPACE__ . '\Promotion', 'id', 'promotion_id');
    }

    public function product()
    {
        return $this->hasOne(__NAMESPACE__ . '\Product', 'id', 'product_id');
    }

    public function category()
    {
        return $this->hasOne(__NAMESPACE__ . '\Category', 'id', 'category_id');
    }

    public function giftProduct()
    {
        return $this->hasOne(__NAMESPACE__ . '\Product', 'id', 'gift_product_id');
    }
}

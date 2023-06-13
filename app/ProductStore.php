<?php
/**
 * User: kpistech2
 * Date: 2020-06-06
 * Time: 20:35
 */

namespace App;


class ProductStore extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_stores';

    protected $fillable = [
        "product_id",
        "store_id",
        "is_active",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}

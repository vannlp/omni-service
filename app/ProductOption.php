<?php
/**
 * User: kpistech2
 * Date: 2020-06-06
 * Time: 19:38
 */

namespace App;


class ProductOption extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_options';

    protected $fillable = [
        "product_id",
        "option_id",
        "values",
        "is_active",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function option()
    {
        return $this->hasOne(CatalogOption::class, 'id', 'option_id');
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}

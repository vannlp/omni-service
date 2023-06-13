<?php
/**
 * User: dai.ho
 * Date: 15/05/2020
 * Time: 2:04 PM
 */

namespace App;


class ProductVersion extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_versions';

    protected $fillable = [
        "product_id",
        "version_product_id",
        "price",
        "version",
        "product_version",
        "is_active",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function productVersion()
    {
        return $this->hasOne(Product::class, 'id', 'version_product_id');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }
}

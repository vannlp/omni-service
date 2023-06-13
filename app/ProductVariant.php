<?php

namespace App;

class ProductVariant extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_variants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "product_id",
        "product_attributes",
        "product_code",
        "price",
        "image",
        "inventory",
        "is_active",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted",
        "deleted_by"
    ];

    protected $casts = [
        'product_attributes' => 'json'
    ];

    /**
     * Belongs to product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Has many promotions
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function promotions()
    {
        return $this->hasMany(ProductVariantPromotion::class, 'product_variant_id', 'id');
    }

}
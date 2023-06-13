<?php

namespace App;

class ProductVariantPromotion extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_variant_promotions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "product_variant_id",
        "user_group_id",
        "order",
        "priority",
        "price",
        "start_date",
        "end_date",
        "is_default",
        "is_active",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted",
        "deleted_by"
    ];

    /**
     * Belongs to product variant
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id', 'id');
    }

    /**
     * Belongs to user group
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userGroups()
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id', 'id');
    }
}
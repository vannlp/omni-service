<?php
/**
 * User: dai.ho
 * Date: 15/05/2020
 * Time: 1:29 PM
 */

namespace App;


class ProductPromotion extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_promotions';

    protected $fillable = [
        "product_id",
        "promotion_id",
        "city_code_promotion",
        "district_code_promotion",
        "priority",
        "price",
        "start_date",
        "end_date",
        "is_default",
        "is_active",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function userGroup()
    {
        return $this->hasOne(UserGroup::class, 'id', 'user_group_id');
    }

    public function promotionProgram()
    {
        return $this->belongsTo(PromotionProgram::class, 'id', 'promotion_id');
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
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

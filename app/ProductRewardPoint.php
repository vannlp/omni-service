<?php
/**
 * User: kpistech2
 * Date: 2020-06-06
 * Time: 22:35
 */

namespace App;


class ProductRewardPoint extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_reward_points';

    protected $fillable = [
        "product_id",
        "user_group_id",
        "point",
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

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}

<?php


namespace App\V1\Models;


use App\CouponCategory;

class CouponCategoryModel extends AbstractModel
{
    /**
     * CouponCategoryModel constructor.
     * @param CouponCategory|null $model
     */
    public function __construct(CouponCategory $model = null)
    {
        parent::__construct($model);
    }
}
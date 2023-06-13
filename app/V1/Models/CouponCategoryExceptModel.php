<?php


namespace App\V1\Models;


use App\CouponCategoryexcept;

class CouponCategoryExceptModel extends AbstractModel
{
    /**
     * CouponCategoryModel constructor.
     * @param CouponCategoryexcept|null $model
     */
    public function __construct(CouponCategoryexcept $model = null)
    {
        parent::__construct($model);
    }
}
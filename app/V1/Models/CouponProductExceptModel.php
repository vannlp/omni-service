<?php


namespace App\V1\Models;


use App\CouponProductexcept;

class CouponProductExceptModel extends AbstractModel
{
    /**
     * CouponProductModel constructor.
     * @param CouponProductexcept|null $model
     */
    public function __construct(CouponProductexcept $model = null)
    {
        parent::__construct($model);
    }
}
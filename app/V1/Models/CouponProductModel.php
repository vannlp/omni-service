<?php


namespace App\V1\Models;


use App\CouponProduct;

class CouponProductModel extends AbstractModel
{
    /**
     * CouponProductModel constructor.
     * @param CouponProduct|null $model
     */
    public function __construct(CouponProduct $model = null)
    {
        parent::__construct($model);
    }
}
<?php


namespace App\V1\Models;


use App\Coupon;
use App\CouponCategory;
use App\CouponCategoryexcept;
use App\CouponProduct;
use App\CouponProductexcept;
use App\Distributor;
use App\Supports\Message;
use App\TM;

class DistributorModel extends AbstractModel
{
    /**
     * CouponModel constructor.
     * @param Coupon|null $model
     */
    public function __construct(Distributor $model = null)
    {
        parent::__construct($model);
    }
}
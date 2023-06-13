<?php


namespace App\V1\Models;


use App\CouponCode;

class CouponCodeModel extends AbstractModel
{
     /**
      * CouponCategoryModel constructor.
      * @param CouponCode|null $model
      */
     public function __construct(CouponCode $model = null)
     {
          parent::__construct($model);
     }
}

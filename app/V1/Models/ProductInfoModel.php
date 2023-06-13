<?php


namespace App\V1\Models;


use App\ProductInfo;

class ProductInfoModel extends AbstractModel
{
    /**
     * ProductInfoModel constructor.
     * @param ProductInfo|null $model
     */
    public function __construct(ProductInfo $model = null)
    {
        parent::__construct($model);
    }

}
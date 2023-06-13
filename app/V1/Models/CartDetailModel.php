<?php


namespace App\V1\Models;


use App\CartDetail;

class CartDetailModel extends AbstractModel
{
    public function __construct(CartDetail $model = null)
    {
        parent::__construct($model);
    }
}
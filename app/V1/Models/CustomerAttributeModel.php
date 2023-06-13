<?php


namespace App\V1\Models;


use App\CustomerAttribute;

class CustomerAttributeModel extends AbstractModel
{
    public function __construct(CustomerAttribute $model = null)
    {
        parent::__construct($model);
    }
}
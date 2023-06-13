<?php


namespace App\V1\Models;


use App\UserCustomer;

class UserCustomerModel extends AbstractModel
{
    public function __construct(UserCustomer $model = null)
    {
        parent::__construct( $model);
    }
}
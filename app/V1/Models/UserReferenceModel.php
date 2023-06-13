<?php


namespace App\V1\Models;


use App\UserReference;

class UserReferenceModel extends AbstractModel
{
    public function __construct(UserReference $model = null)
    {
        parent::__construct($model);
    }
}
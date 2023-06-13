<?php


namespace App\V1\Models;


use App\VirtualAccount;

class VirtualAccountModel extends AbstractModel
{
    public function __construct(VirtualAccount $model = null)
    {
        parent::__construct($model);
    }
}
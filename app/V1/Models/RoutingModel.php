<?php


namespace App\V1\Models;


use App\Routing;

class RoutingModel extends AbstractModel
{
    public function __construct(Routing $model = null)
    {
        parent::__construct($model);
    }
}

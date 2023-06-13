<?php


namespace App\V1\Models;


use App\VisitPlan;

class VisitPlanModel extends AbstractModel
{
    public function __construct(VisitPlan $model = null)
    {
        parent::__construct($model);
    }
}

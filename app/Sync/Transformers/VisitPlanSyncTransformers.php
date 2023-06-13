<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\Sync\Transformers;

use App\Supports\TM_Error;
use App\VisitPlan;
use League\Fractal\TransformerAbstract;

class VisitPlanSyncTransformers extends TransformerAbstract
{
    public function transform(VisitPlan $visitPlan)
    {
        try {
            return [
                'visit_plan_id'     => $visitPlan->visit_plan_id,
                'routing_id'        => $visitPlan->routing_id,
                'shop_id'           => $visitPlan->shop_id,
                'staff_id'          => $visitPlan->staff_id,
                'from_date'         => $visitPlan->from_date,
                'to_date'           => $visitPlan->to_date,
                'status'            => $visitPlan->status,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

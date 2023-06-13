<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\Supports\Message;
use App\TM;
use App\V1\Models\AbstractModel;
use App\VisitPlan;

class VisitPlanModel extends AbstractModel
{
    public function __construct(VisitPlan $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $companyId                       = !empty($input['company_id']) ? $input['company_id'] : null;

        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $visit_plan = VisitPlan::where('visit_plan_id', $id)->first();
            if (empty($visit_plan)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $visit_plan->visit_plan_id      = $input['visit_plan_id'] ?? $visit_plan['visit_plan_id'];
            $visit_plan->routing_id         = $input['routing_id'] ?? $visit_plan['routing_id'];
            $visit_plan->shop_id            = $input['shop_id'] ?? $visit_plan['shop_id'];
            $visit_plan->staff_id           = $input['staff_id'] ?? $visit_plan['staff_id'];
            $visit_plan->from_date          = $input['from_date'] ?? $visit_plan['from_date'];
            $visit_plan->to_date            = $input['to_date'] ?? $visit_plan['to_date'];
            $visit_plan->status             = $input['status'] ?? $visit_plan['status'];
            $visit_plan->updated_by         = TM::getIDP()->sync_name;
            $visit_plan->updated_at         = date("Y-m-d H:i:s", time());
            $visit_plan->save();
        } else {
            $param = [
                'visit_plan_id'     =>  $input['visit_plan_id'] ?? null,
                'routing_id'        =>  $input['routing_id'] ?? null,
                'shop_id'           =>  $input['shop_id'] ?? null,
                'staff_id'          =>  $input['staff_id'] ?? null,
                'from_date'         =>  $input['from_date'] ?? null,
                'to_date'           =>  $input['to_date'] ?? null,
                'status'            =>  $input['status'] ?? null,
                'created_at'        =>  date("Y-m-d H:i:s", time()),
                'created_by'        =>  TM::getIDP()->sync_name,
                'updated_at'        =>  $input['updated_at'] ?? null,
                'updated_by'        =>  $input['updated_by'] ?? null,
            ];
            $visit_plan = $this->create($param);
        }
        return $visit_plan;
    }
}

<?php

/**
 * User: dai.ho
 * Date: 5/02/2021
 * Time: 9:08 AM
 */

namespace App\Sync\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class VisitPlanUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                =>  'nullable|exists:visit_plans,deleted_at,NULL',
            'visit_plan_id'     =>  'required',
            'routing_id'        =>  'required|exists:routings',
            'shop_id'           =>  'required',
            'staff_id'          =>  'required',
            'status'            =>  'required',
        ];
    }

    protected function attributes()
    {
        return [
            'id'            => Message::get("id"),
            'visit_plan_id' => Message::get("visit_plan_id"),
            'routing_id'    => Message::get("routing_id"),
            'shop_id'       => Message::get("shop_id"),
            'staff_id'      => Message::get("staff_id"),
            'status'        => Message::get("status"),
        ];
    }
}

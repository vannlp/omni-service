<?php

/**
 * User: dai.ho
 * Date: 5/02/2021
 * Time: 9:08 AM
 */

namespace App\Sync\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class RoutingCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                =>  'nullable|exists:routings,routing_code,deleted_at,NULL',
            'routing_id'        =>  'required|unique:routings',
            'routing_code'      =>  'required|unique:routings',
            'routing_name'      =>  'required',
            'shop_id'           =>  'required',
            'status'            =>  'required',
        ];
    }

    protected function attributes()
    {
        return [
            'routing_id'    => Message::get("routing_id"),
            'routing_code'  => Message::get("routing_code"),
            'routing_name'  => Message::get("routing_name"),
            'shop_id'       => Message::get("shop_id"),
            'status'        => Message::get("status"),
        ];
    }
}

<?php

/**
 * User: dai.ho
 * Date: 5/02/2021
 * Time: 9:08 AM
 */

namespace App\Sync\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class RoutingCustomerCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                         =>  'nullable|exists:routing_customers,deleted_at,NULL',
            'routing_customer_id'        =>  'required|unique:routing_customers',
            'routing_id'                 =>  'required|exists:routings',
            'customer_id'                =>  'required',
            'shop_id'                    =>  'required',
            'start_date'                 =>  'date_format:Y-m-d',
            'end_date'                   =>  'date_format:Y-m-d',
            'last_order'                 =>  'date_format:Y-m-d',
            'last_approve_order'         =>  'date_format:Y-m-d',
            'status'                     =>  'required',
        ];
    }

    protected function attributes()
    {
        return [
            'id'                        => Message::get('id'),
            'routing_customer_id'       => Message::get("routing_customer_id"),
            'routing_id'                => Message::get("routing_id"),
            'customer_id'               => Message::get("customer_id"),
            'shop_id'                   => Message::get("shop_id"),
            'status'                    => Message::get("status"),
            'start_date'                => Message::get("start_date"),
            'end_date'                  => Message::get("end_date"),
            'last_order'                => Message::get("last_order"),
            'last_approve_order'        => Message::get("last_approve_order"),
        ];
    }
}

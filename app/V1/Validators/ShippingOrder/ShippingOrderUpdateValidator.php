<?php
/**
 * User: dai.ho
 * Date: 29/06/2020
 * Time: 1:17 PM
 */

namespace App\V1\Validators\ShippingOrder;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ShippingOrderUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            "status"                    => "required",
            "details"                  => "required",
            "details.*.ship_qty"        => "required|numeric",
        ];
    }

    protected function attributes()
    {
        return [
            'status'                    => Message::get("status"),
            'details'                    => Message::get("details"),
            'details.*.ship_qty'        => Message::get("ship_qty")
        ];
    }
}
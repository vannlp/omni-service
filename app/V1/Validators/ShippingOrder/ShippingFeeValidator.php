<?php
/**
 * User: dai.ho
 * Date: 29/06/2020
 * Time: 1:29 PM
 */

namespace App\V1\Validators\ShippingOrder;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ShippingFeeValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'from_city'     => 'required',
            'from_district' => 'required',
            'to_city'       => 'required',
            'to_district'   => 'required',
            'weight'        => 'required|numeric',
        ];
    }

    protected function attributes()
    {
        return [
            'from_city'     => Message::get("pick_address"),
            'from_district' => Message::get("pick_address"),
            'to_city'       => Message::get("receiver_address"),
            'to_district'   => Message::get("receiver_address"),
            'weight'        => Message::get("weight"),
        ];
    }
}
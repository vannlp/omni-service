<?php
/**
 * User: dai.ho
 * Date: 8/06/2020
 * Time: 2:39 PM
 */

namespace App\V1\Validators\ShippingMethod;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ShippingMethodCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'  => 'required',
            'name'  => 'required',
            'price' => 'nullable|numeric',
        ];
    }

    protected function attributes()
    {
        return [
            'code'  => Message::get("code"),
            'name'  => Message::get("alternative_name"),
            'price' => Message::get("price"),
        ];
    }
}
<?php
/**
 * User: dai.ho
 * Date: 1/06/2020
 * Time: 1:30 PM
 */

namespace App\V1\Validators\ConfigShipping;

use App\ConfigShipping;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ConfigShippingCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'                    => ['required',
            function ($attribute, $value, $fail) {
                if (!empty($value)) {
                    $task = ConfigShipping::Model()->where([
                        'code' => $value
                        ])->first();
                    if (!empty($task)) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                }
                return true;
            }
        ],
            'delivery_code'           => 'required',
            'time_from'               => 'required',
            'time_to'                 => 'required',
            'time_type'               => 'required',
            'shipping_partner_code'   => 'required',
            'shipping_fee'            => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'code'                      => Message::get("code"),
            'delivery_code'             => Message::get("delivery_code"),
            'time_from'                 => Message::get("time_from"),
            'time_to'                   => Message::get("time_to"),
            'time_type'                 => Message::get("time_type"),
            'shipping_partner_code'     => Message::get("shipping_partner_code"),
            'shipping_fee'              => Message::get("shipping_fee"),
            
        ];
    }
}
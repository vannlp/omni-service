<?php
/**
 * User: kpistech2
 * Date: 2020-08-02
 * Time: 16:37
 */

namespace App\Http\Validators;


use App\Supports\Message;

class CustomerLoginValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'device_id'    => 'required',
            'device_token' => 'required',
            'device_type'  => 'required',
            'device_id'    => 'required',
            'phone'        => 'required|exists:users,phone',
            'password'     => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'phone'        => Message::get("phone"),
            'device_id'    => Message::get("device_id"),
            'device_type'  => Message::get("device_type"),
            'password'     => Message::get("password"),
            'device_token' => Message::get("device_token"),
        ];
    }
}
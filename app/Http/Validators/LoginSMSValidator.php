<?php

namespace App\Http\Validators;

use App\Supports\Message;

class LoginSMSValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'email'        => 'nullable|email',
            'phone'        => 'required',
            'device_token' => 'required',
            'device_type'  => 'required',
            'device_id'    => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'phone'        => Message::get("phone"),
            'email'        => Message::get("email"),
            'device_token' => Message::get("device_token"),
            'device_type'  => Message::get("device_type"),
            'device_id'    => Message::get("device_id")
        ];
    }
}
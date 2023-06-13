<?php

namespace App\Http\Validators\Client;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ResetPasswordValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'phone'    => 'required',
            'password' => 'required',
            'sms_code' => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'phone'    => Message::get("phone"),
            'password' => Message::get("password"),
            'sms_code' => Message::get("sms_code")
        ];
    }
}
<?php

namespace App\Http\Validators\Client;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class CheckOTPRegisterValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'phone'    => 'required',
            'sms_code' => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'phone'    => Message::get("phone"),
            'sms_code' => Message::get("sms_code")
        ];
    }
}
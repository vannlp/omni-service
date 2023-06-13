<?php

namespace App\Http\Validators\Client;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class RegisterValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'phone'    => 'required|numeric|not_in:0',
//            'sms_code' => 'required',
            'password' => 'required',
            'name'     => 'required',
            'email'    => 'nullable|email',
            'gender'   => 'nullable|in:M,F,O',
            'birthday' => 'nullable|date_format:Y-m-d',
            'group_id' => 'required',
            'group_code' => 'required',
            'group_name' => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'phone'    => Message::get("phone"),
//            'sms_code' => Message::get("sms_code"),
            'password' => Message::get("password"),
            'name'     => Message::get("name"),
            'email'    => Message::get("email")
        ];
    }
}
<?php

namespace App\Http\Validators\Client;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class LoginValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            // 'phone'    => 'required|exists:users,phone',
            'phone'    => 'required',
            'password' => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'phone'    => Message::get("phone"),
            'password' => Message::get("password")
        ];
    }
}
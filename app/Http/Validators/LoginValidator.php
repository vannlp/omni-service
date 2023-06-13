<?php

namespace App\Http\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

//namespace App\V1\Validators;

/**
 * Class LoginValidator
 *
 * @package App\V1\Validators
 */
class LoginValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'     => 'required|exists:users,code',
            'password' => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'code'         => Message::get("code"),
            'password'     => Message::get("password"),
            'device_token' => Message::get("device_token"),
        ];
    }
}
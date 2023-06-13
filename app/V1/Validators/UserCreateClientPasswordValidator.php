<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class UserCreateClientPasswordValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'user_id'  => 'required|exists:users,id,deleted_at,NULL',
            'password' => 'required|min:8',
        ];
    }

    protected function attributes()
    {
        return [
            'user_id'  => Message::get("users"),
            'password' => Message::get("password"),
        ];
    }
}
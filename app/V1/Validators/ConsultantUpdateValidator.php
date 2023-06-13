<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ConsultantUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'      => 'exists:consultants,id,deleted_at,NULL',
            'user_id' => 'required|exists:users,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'user_id' => Message::get("users"),
        ];
    }
}
<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class AcceptOrderValidator extends ValidatorBase

{
    protected function rules()
    {
        return [
            'type' => 'in:PRODUCT,SERVICE'
        ];
    }

    protected function attributes()
    {
        return [
            'type' => Message::get("type")
        ];
    }
}
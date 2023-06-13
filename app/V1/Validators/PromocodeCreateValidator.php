<?php


namespace App\V1\Validators;


use App\Promocode;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PromocodeCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'                            => 'required|max:100',
            'name'                            => 'required|max:100',
        ];
    }

    protected function attributes()
    {
        return [
            'code'                            => Message::get("code"),
            'value'                            => Message::get("value"),
        ];
    }
}
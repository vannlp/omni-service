<?php


namespace App\V1\Validators;


use App\Rotation;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class RotationCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'                    => 'required',
            'name'                    => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'code'                            => Message::get("code"),
            'name'                            => Message::get("name"),
        ];
    }
}
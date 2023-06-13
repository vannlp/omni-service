<?php


namespace App\V1\Validators;


use App\Rotation;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Http\Request;

class RotationUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'     => 'required',
            'code'   => 'nullable',
            'name'   => 'nullable'
        ];
    }

    protected function attributes()
    {
        return [
            'code' => Message::get("code"),
            'name' => Message::get("name")
        ];
    }
}
<?php


namespace App\V1\Validators;


use App\Rotation;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class RotationConditionCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'rotation_id'             => 'required',
            'name'                    => 'required',
            'code'                    => 'required|unique:rotation_conditions,code',
            'type'                    => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'code'                            => Message::get("code"),
            'name'                            => Message::get("name"),
            'rotation_id'                     => Message::get("rotation_id"),
            'type'                            => Message::get("type"),
        ];
    }
}
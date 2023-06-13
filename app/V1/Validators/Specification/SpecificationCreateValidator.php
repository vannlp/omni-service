<?php

namespace App\V1\Validators\Specification;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class SpecificationCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'  => 'required',
            'value' => 'required|numeric',
        ];
    }

    protected function attributes()
    {
        return [
            'code'  => Message::get("code"),
            'value' => Message::get("value")
        ];
    }
}
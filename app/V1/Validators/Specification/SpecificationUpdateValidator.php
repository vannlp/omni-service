<?php

namespace App\V1\Validators\Specification;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class SpecificationUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'    => 'required|exists:specifications,id,deleted_at,NULL',
            'code'  => 'required',
            'value' => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'id'    => Message::get("id"),
            'code'  => Message::get("code"),
            'value' => Message::get("value")
        ];
    }
}
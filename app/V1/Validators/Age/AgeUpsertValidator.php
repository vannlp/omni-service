<?php

namespace App\V1\Validators\Age;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class AgeUpsertValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'   => 'nullable|exists:ages,id,deleted_at,NULL',
            'code' => 'required',
            'name' => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'code' => Message::get("code"),
            'name' => Message::get("alternative_name"),
        ];
    }
}
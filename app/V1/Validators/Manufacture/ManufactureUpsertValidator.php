<?php

namespace App\V1\Validators\Manufacture;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ManufactureUpsertValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'   => 'nullable|exists:manufactures,id,deleted_at,NULL',
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
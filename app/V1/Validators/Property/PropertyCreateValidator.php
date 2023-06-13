<?php

namespace App\V1\Validators\Property;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PropertyCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code' => 'required|unique_create_company_delete:properties,code',
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
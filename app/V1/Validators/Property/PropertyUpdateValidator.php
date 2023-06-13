<?php

namespace App\V1\Validators\Property;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PropertyUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'   => 'nullable|exists:properties,id,deleted_at,NULL',
            'code' => 'required|unique_update_company_delete:properties,code',
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
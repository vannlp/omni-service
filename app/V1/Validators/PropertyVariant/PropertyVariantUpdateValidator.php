<?php

namespace App\V1\Validators\PropertyVariant;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PropertyVariantUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'          => 'nullable|exists:property_variants,id,deleted_at,NULL',
            'code'        => 'required|unique_update_company_delete:property_variants,code',
            'property_id' => 'nullable|exists:properties,id,deleted_at,NULL',
            'name'        => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'property_id' => Message::get("properties"),
            'code'        => Message::get("code"),
            'name'        => Message::get("alternative_name"),
        ];
    }
}
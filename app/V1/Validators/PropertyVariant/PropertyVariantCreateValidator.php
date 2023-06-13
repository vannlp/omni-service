<?php

namespace App\V1\Validators\PropertyVariant;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PropertyVariantCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'property_id' => 'required|exists:properties,id,deleted_at,NULL',
            'code'        => 'required|unique_create_company_delete:property_variants,code',
            'name'        => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'property_id' => Message::get("properties"),
            'code' => Message::get("code"),
            'name' => Message::get("alternative_name"),
        ];
    }
}
<?php

namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class BrandUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'name'        => 'required|string|max:70',
            'description' => 'nullable|string|max:150',
            'store_id'    => 'required|exists:stores,id,deleted_at,NULL'
        ];
    }

    protected function attributes()
    {
        return [
            'name'        => Message::get("name"),
            'description' => Message::get("description"),
            'store_id'    => Message::get("store_id"),
        ];
    }
}
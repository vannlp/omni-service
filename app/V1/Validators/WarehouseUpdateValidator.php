<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use App\Warehouse;

class WarehouseUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'          => 'required|exists:warehouses,id,deleted_at,NULL',
            'code'        => 'required|unique_update:warehouses',
            'name'        => 'nullable|max:500',
            'address'     => 'nullable|max:500',
            'description' => 'nullable|max:500',
        ];
    }

    protected function attributes()
    {
        return [
            'code'        => Message::get("code"),
            'name'        => Message::get("alternative_name"),
            'address'     => Message::get("address"),
            'description' => Message::get("description"),
        ];
    }
}
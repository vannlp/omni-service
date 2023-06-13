<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use App\Warehouse;

class WarehouseCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code' => [
                'required',
                'max:200',
                function ($attribute, $value, $fail) {
                    $warehouse = Warehouse::model()->where('code', $value)->first();
                    if (!empty($warehouse)) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                    return true;
                }
            ],
            'name' => 'required|max:500',
            'address' => 'nullable|max:500',
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
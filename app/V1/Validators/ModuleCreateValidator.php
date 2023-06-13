<?php

namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Module;
use App\Supports\Message;

class ModuleCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'module_type' => 'required|max:50',
            'module_data' => 'required',
            'company_id'  => 'required|exists:companies,id,deleted_at,NULL',
            'module_name' => 'required|max:20',
            'module_code' => [
                'required',
                'max:20',
                function ($attribute, $value, $fail) {
                    $module = Module::model()->where('module_code', $value)->first();
                    if (!empty($module)) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                    return true;
                }
            ]
        ];
    }

    protected function attributes()
    {
        return [
            'module_type' => Message::get("module_type"),
            'module_data' => Message::get("module_data"),
            'company_id'  => Message::get("company_id"),
            'module_code' => Message::get("module_code"),
            'module_name' => Message::get("module_name")
        ];
    }
}

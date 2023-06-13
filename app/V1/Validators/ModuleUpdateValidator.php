<?php

namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Module;
use App\Supports\Message;

class ModuleUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'          => 'required|exists:modules,id,deleted_at,NULL',
            'module_type' => 'max:50',
            'company_id'  => 'exists:companies,id,deleted_at,NULL',
            'module_name' => 'max:20',
            'module_code' => [
                'max:20',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $module = Module::model()->where([
                            'module_code' => $value,
                            ['id', '<>', $value]
                        ])->first();
                        if ($module) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                    return true;
                }
            ]
        ];
    }

    protected function attributes()
    {
        return [
            'id'          => Message::get("id"),
            'module_type' => Message::get("module_type"),
            'company_id'  => Message::get("company_id"),
            'module_code' => Message::get("module_code"),
            'module_name' => Message::get("module_name")
        ];
    }
}

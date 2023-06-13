<?php

namespace App\V1\Transformers\Module;

use App\Module;
use League\Fractal\TransformerAbstract;

class ModuleTransformer extends TransformerAbstract
{
    public function transform(Module $module)
    {
        return [
            'module_type' => $module->module_type,
            'module_data' => $module->module_data,
            'company_id'  => $module->company->name,
            'module_name' => $module->module_name,
            'module_code' => $module->module_code
        ];
    }
}

<?php

namespace App\V1\Models;

use App\Module;
use App\TM;

class ModuleModel extends AbstractModel
{
    public function __construct(Module $model = null)
    {
        parent::__construct($model);
    }

    public function create($input)
    {
        $params = [
            'deleted'    => 0,
            'created_at' => date("Y-m-d H:i:s", time()),
            'created_by' => TM::getCurrentUserId(),
            'updated_at' => date("Y-m-d H:i:s", time()),
            'updated_by' => TM::getCurrentUserId()
        ];
        $params = array_merge($input, $params);

        return $this->model->create($params);
    }

    public function update($input)
    {
        $module = $this->model->findOrFail($input['id']);
        $params = [
            'module_type' => $input['module_type'] ?? $module->module_type,
            'module_data' => $input['module_data'] ?? $module->module_data,
            'company_id'  => $input['company_id'] ?? $module->company_id,
            'module_name' => $input['module_name'] ?? $module->module_name,
            'module_code' => $input['module_code'] ?? $module->module_code,
            'updated_at'  => date("Y-m-d H:i:s", time()),
            'updated_by'  => TM::getCurrentUserId()
        ];
        $module->update($params);

        return $module;
    }
}

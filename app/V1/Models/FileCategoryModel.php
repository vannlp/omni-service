<?php

namespace App\V1\Models;

use App\FileCategory;
use App\TM;

class FileCategoryModel extends AbstractModel
{
    public function __construct(FileCategory $model = null)
    {
        parent::__construct($model);
    }

    public function create($input)
    {
        $params = [
            'category_name' => $input['name'],
            'store_id'      => TM::getCurrentStoreId(),
            'deleted'       => 0,
            'created_at'    => date("Y-m-d H:i:s", time()),
            'created_by'    => TM::getCurrentUserId(),
            'updated_at'    => date("Y-m-d H:i:s", time()),
            'updated_by'    => TM::getCurrentUserId(),
        ];
        return $this->model->create($params);
    }

    public function update($input)
    {
        $params = [
            'category_name' => $input['name'],
            'store_id'      => TM::getCurrentStoreId(),
            'updated_at'    => date("Y-m-d H:i:s", time()),
            'updated_by'    => TM::getCurrentUserId(),
        ];
        $category = $this->model->findOrFail($input['id']);
        $category->update($params);

        return $category;
    }
}

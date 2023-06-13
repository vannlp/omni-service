<?php

namespace App\V1\Models;

use App\FileCloud;
use App\TM;

class FileCloudModel extends AbstractModel
{
    public function __construct(FileCloud $model = null)
    {
        parent::__construct($model);
    }

    public function create($input)
    {
        $params = [
            'title'      => $input['title'],
            'url'        => $input['url'],
            'path'       => $input['path'],
            'category'   => $input['category'] ?? null,
            'shop'       => $input['shop'],
            'deleted'    => 0,
            'created_at' => date("Y-m-d H:i:s", time()),
            'created_by' => TM::getCurrentUserId(),
            'updated_at' => date("Y-m-d H:i:s", time()),
            'updated_by' => TM::getCurrentUserId()
        ];

        return $this->model->create($params);
    }

    public function update($input)
    {
        $file = $this->model->findOrFail($input['id']);
        $params = [
            'title'      => $input['title'] ?? $file->title,
            'path'       => $input['path'] ?? $file->path,
            'category'   => $input['category'] ?? $file->category,
            'shop'       => $input['shop'] ?? $file->shop,
            'updated_at' => date("Y-m-d H:i:s", time()),
            'updated_by' => TM::getCurrentUserId()
        ];
        $file->update($params);

        return $file;
    }
}

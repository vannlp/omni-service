<?php


namespace App\V1\Transformers\FileCloud;

use App\FileCloud;
use League\Fractal\TransformerAbstract;

class FileCloudListTransformer extends TransformerAbstract
{
    public function transform(FileCloud $file)
    {
        return [
            'title'              => $file->title,
            'file_category_id'   => $file->category ?? null,
            'file_category_name' => $file->fileCategory->category_name ?? null,
            'shop'               => $file->store->name ?? '',
            'path'               => $file->url,
            'deleted'            => $file->deleted,
            'created_at'         => date('d-m-Y', strtotime($file->created_at)),
            'created_by'         => object_get($file, 'createdBy.profile.full_name'),
            'updated_at'         => date('d-m-Y', strtotime($file->updated_at)),
            'updated_by'         => object_get($file, 'updatedBy.profile.full_name')
        ];
    }
}

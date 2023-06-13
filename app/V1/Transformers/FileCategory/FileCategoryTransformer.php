<?php


namespace App\V1\Transformers\FileCategory;


use App\FileCategory;
use League\Fractal\TransformerAbstract;

class FileCategoryTransformer extends TransformerAbstract
{
    public function transform(FileCategory $fileCategory)
    {
        return [
            'id'         => $fileCategory->id,
            'name'       => $fileCategory->category_name,
            'store_id'   => $fileCategory->store_id,
            'deleted'    => $fileCategory->deleted,
            'created_at' => date('d-m-Y', strtotime($fileCategory->created_at)),
            'created_by' => object_get($fileCategory, 'createdBy.profile.full_name'),
            'updated_at' => date('d-m-Y', strtotime($fileCategory->updated_at)),
            'updated_by' => object_get($fileCategory, 'updatedBy.profile.full_name')
        ];
    }
}
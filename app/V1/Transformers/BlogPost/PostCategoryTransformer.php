<?php


namespace App\V1\Transformers\BlogPost;


use App\PostCategory;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class PostCategoryTransformer extends TransformerAbstract
{
    public function transform(PostCategory $model)
    {
        $folder_path = object_get($model, 'thumbnail.folder.folder_path');
        if (!empty($folder_path)) {
            $folder_path = str_replace("/", ",", $folder_path);
        } else {
            $folder_path = "uploads";
        }
        $folder_path = url('/v0') . "/img/" . $folder_path;
        return [
            'id'            => $model->id,
            'name'          => $model->name,
            'slug'          => $model->slug,
            'status'        => $model->status,
            'order'         => $model->order,
            'description'   => $model->description,
            'parent_id'     => $model->parent_id,
            'thumbnail_id'  => $model->thumbnail_id,
            'thumbnail_url' => !empty($model->thumbnail_id) ? !empty($folder_path) ? $folder_path . ',' . Arr::get($model, "thumbnail.file_name") : null : null,
            'website_id'    => Arr::get($model, 'website_id', null),
            'taxonomy_id'   => $model->taxonomy_id,
            'taxonomy_slug' => Arr::get($model, 'taxonomy.slug', null),
            'taxonomy_name' => Arr::get($model, 'taxonomy.name', null),
            'deleted'       => $model->deleted,
            'created_at'    => date('d-m-Y', strtotime($model->created_at)),
            'updated_at'    => date('d-m-Y', strtotime($model->updated_at)),
            'created_by'    => object_get($model, 'createdBy.profile.full_name',
                $model->created_by),
            'updated_by'    => object_get($model, 'updatedBy.profile.full_name',
                $model->updated_by),
        ];
    }
}
<?php


namespace App\V1\Transformers\Post;


use App\PostCategory;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class PostCategoryTransformer extends TransformerAbstract
{
    public function transform(PostCategory $item)
    {
        try {
            return [
                'id'                 => $item->id,
                'code'              => $item->code,
                'title'              => $item->title,
                'slug'               => $item->slug,
                'description'        => $item->description,
                'order'              => $item->order,
                'is_show'            => $item->is_show,
                'created_at'         => date('d-m-Y', strtotime($item->created_at)),
                'created_by'         => object_get($item, "createdBy.profile.full_name"),
                'updated_at'         => date('d-m-Y', strtotime($item->updated_at)),
                'updated_by'         => object_get($item, "updatedBy.profile.full_name"),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

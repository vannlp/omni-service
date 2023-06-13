<?php

namespace App\V1\Transformers\Post;

use App\Post;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class PostTransformer extends TransformerAbstract
{
    public function transform(Post $item)
    {
        try {
            $fileCode = Arr::get($item, 'file.code', null);
            return [
                'id'                => $item->id,
                'title'             => $item->title,
                'slug'              => $item->slug,
                'thumbnail'         => $item->thumbnail,
                'thumbnail_url'     => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                'content'           => $item->content,
                'view'              => $item->view,
                'short_description' => $item->short_description,
                'category_id'       => $item->category_id,
                'category_title'    => Arr::get($item, 'category.title', null),
                'company_id'        => $item->company_id,
                'company_code'      => Arr::get($item, 'company.code', null),
                'company_name'      => Arr::get($item, 'company.name', null),
                'tags'              => $item->tags,
                'author'            => $item->author,
                'date'              => date('d-m-Y H:i:s', strtotime($item->date)),
                'is_show'           => $item->is_show,
                'meta_title'        => $item->meta_title,
                'meta_description'  => $item->meta_description,
                'meta_keyword'      => $item->meta_keyword,
                'meta_robot'        => $item->meta_robot,
                'created_at'        => date('d-m-Y', strtotime($item->created_at)),
                'created_by'        => object_get($item, "createdBy.profile.full_name"),
                'updated_at'        => date('d-m-Y', strtotime($item->updated_at)),
                'updated_by'        => object_get($item, "updatedBy.profile.full_name"),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

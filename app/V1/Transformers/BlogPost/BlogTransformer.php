<?php


namespace App\V1\Transformers\BlogPost;


use App\Blog;
use League\Fractal\TransformerAbstract;

class BlogTransformer extends TransformerAbstract
{
    public function transform(Blog $blog)
    {
        return [
            'id'          => $blog->id,
            'name'        => $blog->name,
            'description' => $blog->description,
            'keyword'     => $blog->keyword,
            'website_id'  => $blog->website_id,
            'icon'        => $blog->icon,
            'favicon'     => $blog->favicon,
            'is_active'   => $blog->is_active,
            'deleted'     => $blog->deleted,
            'created_at'  => date('d-m-Y', strtotime($blog->created_at)),
            'created_by'  => object_get($blog, 'createdBy.profile.full_name',
                $blog->created_by),
            'updated_at'  => date('d-m-Y', strtotime($blog->updated_at)),
            'updated_by'  => object_get($blog, 'updatedBy.profile.full_name',
                $blog->updated_by),
        ];
    }
}
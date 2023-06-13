<?php


namespace App\V1\Transformers\BlogPost;


use App\BlogCategory;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class BlogCategoryTransformer extends TransformerAbstract
{
    public function transform(BlogCategory $blogCategory)
    {
        try {
            return [
                "id"               => $blogCategory->id,
                "name"             => $blogCategory->name,
                "slug"             => object_get($blogCategory, 'slug', null),
                "blog_id"          => $blogCategory->blog_id,
                "website_id"       => object_get($blogCategory, 'website_id', null),
                "blog_name"        => object_get($blogCategory, 'blog.name', null),
                "blog_description" => object_get($blogCategory, 'blog.description', null),
                "blog_keyword"     => object_get($blogCategory, 'blog.keyword', null),
                "blog_icon"        => object_get($blogCategory, 'blog.icon', null),
                "blog_favicon"     => object_get($blogCategory, 'blog.favicon', null),
                'is_active'        => $blogCategory->is_active,
                'created_at'       => date('d-m-Y', strtotime($blogCategory->created_at)),
                'updated_at'       => date('d-m-Y', strtotime($blogCategory->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
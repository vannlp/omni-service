<?php


namespace App\V1\Transformers\BlogPost;


use App\PostTag;
use League\Fractal\TransformerAbstract;

class TagTransformer extends TransformerAbstract
{
    public function transform(PostTag $tag)
    {
        return [
            'id'   => $tag->id,
            'name' => $tag->name,
            'slug' => $tag->slug
        ];
    }
}
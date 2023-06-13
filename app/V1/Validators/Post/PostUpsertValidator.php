<?php

namespace App\V1\Validators\Post;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PostUpsertValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                => 'nullable|exists:posts,id,deleted_at,NULL',
            'title'             => 'required',
//            'thumbnail'         => 'nullable|exists:files,id,deleted_at,NULL',
            'content'           => 'required',
            'short_description' => 'nullable',
            'category_id'       => 'required|exists:post_categories,id,deleted_at,NULL',
            'tags'              => 'nullable',
            'author'            => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'id'                => Message::get("id"),
            'title'             => Message::get("title"),
            'thumbnail'         => Message::get("thumbnail"),
            'short_description' => Message::get("description"),
            'content'           => Message::get("content"),
            'category_id'       => Message::get("category_id"),
            'tags'              => Message::get("tags"),
            'author'            => Message::get("author"),
        ];
    }
}

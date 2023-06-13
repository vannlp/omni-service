<?php

namespace App\V1\Validators\Post;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PostCategoryUpsertValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'          => 'nullable|exists:post_categories,id,deleted_at,NULL',
            'title'       => 'required',
            'thumbnail'   => 'nullable|exists:files,id,deleted_at,NULL',
            'description' => 'nullable'
        ];
    }

    protected function attributes()
    {
        return [
            'id'          => Message::get("id"),
            'title'       => Message::get("title"),
            'thumbnail'   => Message::get("thumbnail"),
            'description' => Message::get("description"),
        ];
    }
}

<?php


namespace App\V1\Validators\BlogPost;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PostCommentUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'content'    => 'nullable',
            'parent_id'  => 'nullable|exists:post_comments,id,deleted_at,NULL',
            'post_id'    => 'nullable|exists:posts,id,deleted_at,NULL',
            'website_id' => 'nullable|exists:websites,id,deleted_at,NULL',
            'rate'       => 'nullable',
            'count_like' => 'nullable',
        ];
    }

    protected function attributes()
    {
        return [
            'content'    => Message::get("content"),
            'parent_id'  => Message::get("parent_id"),
            'post_id'    => Message::get("post_id"),
            'rate'       => Message::get("rate"),
            'count_like' => Message::get("count_like"),
            'website_id' => Message::get("website_id"),
        ];
    }
}
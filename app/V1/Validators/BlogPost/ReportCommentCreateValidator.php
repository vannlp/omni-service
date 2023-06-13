<?php


namespace App\V1\Validators\BlogPost;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ReportCommentCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'content'    => 'required',
            'comment_id' => 'required|exists:post_comments,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'content'    => Message::get("content"),
            'comment_id' => Message::get("comment_id"),
        ];
    }
}
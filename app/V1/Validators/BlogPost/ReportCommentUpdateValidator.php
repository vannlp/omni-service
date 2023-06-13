<?php


namespace App\V1\Validators\BlogPost;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ReportCommentUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'comment_id' => 'nullable|exists:post_comments,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'comment_id' => Message::get("comment_id"),
        ];
    }
}
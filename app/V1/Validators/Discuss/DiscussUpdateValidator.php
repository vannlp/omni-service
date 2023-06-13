<?php


namespace App\V1\Validators\Discuss;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class DiscussUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'issue_id' => 'nullable|exists:issues,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'issue_id' => Message::get("issue_id"),
            'user_id'  => Message::get("user_id"),
        ];
    }
}
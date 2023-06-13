<?php


namespace App\V1\Validators\Discuss;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class DiscussCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'issue_id'    => 'required|exists:issues,id,deleted_at,NULL',
            'description' => 'required|',
        ];
    }

    protected function attributes()
    {
        return [
            'issue_id'    => Message::get("issue_id"),
            'description' => Message::get("description"),
        ];
    }
}
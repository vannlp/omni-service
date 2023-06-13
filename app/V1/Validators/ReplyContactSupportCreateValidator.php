<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ReplyContactSupportCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'contact_support_id' => 'required|exists:contact_supports,id,deleted_at,NULL',
            'content_reply'      => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'contact_support_id' => Message::get("contact_supports"),
            'content_reply'      => Message::get("content"),
        ];
    }
}
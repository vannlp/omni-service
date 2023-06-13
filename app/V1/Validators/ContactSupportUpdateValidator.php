<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ContactSupportUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'       => 'required|exists:contact_supports,id,deleted_at,NULL',
            'subject'  => 'nullable',
            'content'  => 'nullable',
            'category' => 'nullable|in:ACCOUNT,PAYMENT,SUPPORT_ORDER,OTHER',
            'status'   => 'required|in:NEW,RECEIVED,SOLVED',
        ];
    }

    protected function attributes()
    {
        return [
            'subject'  => Message::get("subject"),
            'content'  => Message::get("content"),
            'category' => Message::get("category"),
            'status'   => Message::get("status"),
        ];
    }
}

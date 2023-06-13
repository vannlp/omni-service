<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ContactSupportCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'subject'  => 'required',
            'content'  => 'required',
            'category' => 'required|in:ACCOUNT,PAYMENT,SUPPORT_ORDER,OTHER',
            'status'   => 'in:NEW,RECEIVED,SOLVED',
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

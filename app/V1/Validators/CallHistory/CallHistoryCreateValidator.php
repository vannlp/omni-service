<?php


namespace App\V1\Validators\CallHistory;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class CallHistoryCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'receiver_id' => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'receiver_id' => Message::get("call_receiver_id"),
        ];
    }
}
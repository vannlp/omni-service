<?php


namespace App\V1\Validators\CallHistory;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class CallHistoryUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'             => 'required|exists:call_histories,id,deleted_at,NULL',
            'caller_id'      => 'required',
            'receiver_id'    => 'required',
            'call_from_time' => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'caller_id'      => Message::get("caller_id"),
            'receiver_id'    => Message::get("call_receiver_id"),
            'call_from_time' => Message::get("call_from_time"),
        ];
    }
}
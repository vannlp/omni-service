<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class OrderHistoryUpsertValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'order_id' => 'required|exists:orders,id,deleted_at,NULL',
            'status'   => 'required|in:NEW,RECEIVED,IN PROGRESS,COMPLETED,CANCELED,RETURNED',
        ];
    }

    protected function attributes()
    {
        return [
            'order_id' => Message::get("order_id"),
            'status'   => Message::get("status"),
        ];
    }
}
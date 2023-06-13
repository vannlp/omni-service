<?php

namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class OrderUpdateStatusValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'     => 'required|exists:orders,id,deleted_at,NULL',
            'status' => 'exists:order_status,code,deleted_at,NULL'
        ];
    }

    protected function attributes()
    {
        return [
            'id'     => Message::get("order_id"),
            'status' => Message::get("status")
        ];
    }
}
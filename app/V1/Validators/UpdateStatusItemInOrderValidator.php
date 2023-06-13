<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class UpdateStatusItemInOrderValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'     => 'required|exists:order_details,id,deleted_at,NULL',
            'status' => 'required|exists:master_data,code,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'id'     => Message::get("id"),
            'status' => Message::get("status"),
        ];
    }
}
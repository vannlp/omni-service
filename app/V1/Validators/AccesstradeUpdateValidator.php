<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class AccesstradeUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'status'                 => 'required',
            'reason'                 => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'status'                 => Message::get("status"),
            'reason'                 => Message::get("reason"),
        ];
    }
}

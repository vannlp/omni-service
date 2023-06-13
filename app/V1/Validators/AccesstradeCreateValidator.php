<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class AccesstradeCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'accesstrade_id'       => 'required',
            'click_id'             => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'accesstrade_id'       => Message::get("accesstrade_id"),
            'click_id'             => Message::get("click_id"),
        ];
    }
}

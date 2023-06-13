<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class WalletCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'money'  => 'required|numeric',
            'status' => 'required|in:' . WALLET_STATUS_WITHDRAW . "," . WALLET_STATUS_RECHARGE . "," . WALLET_STATUS_OTHER
        ];
    }

    protected function attributes()
    {
        return [
            'money'  => Message::get("money"),
            'status' => Message::get("status"),
        ];
    }
}
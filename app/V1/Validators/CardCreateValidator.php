<?php


namespace App\V1\Validators;


use App\Card;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class CardCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'    => [
                'required',
                'max:20',
                function ($attribute, $value, $fail) {
                    $item = Card::model()->where('code', $value)->first();
                    if (!empty($item)) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                    return true;
                }
            ],
            'name'    => 'required|max:100',
            'from'    => 'date_format:Y-n-j',
            'expired' => 'date_format:Y-n-j',
        ];
    }

    protected function attributes()
    {
        return [
            'code'    => Message::get("code"),
            'name'    => Message::get("name"),
            'from'    => Message::get("from"),
            'expired' => Message::get("expired"),
        ];
    }
}
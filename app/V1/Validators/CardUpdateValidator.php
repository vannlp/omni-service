<?php


namespace App\V1\Validators;


use App\Card;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Http\Request;

class CardUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'   => 'required|exists:cards,id,deleted_at,NULL',
            'code' => [
                'nullable',
                'max:20',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    $item = Card::model()->where('code', $value)->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        if (count($item) > 1 || ($input['id'] > 0 && $item[0]['id'] != $input['id'])) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                }
            ],
            'name' => 'nullable|max:50',
        ];
    }

    protected function attributes()
    {
        return [
            'code' => Message::get("code"),
            'name' => Message::get("name"),
        ];
    }
}
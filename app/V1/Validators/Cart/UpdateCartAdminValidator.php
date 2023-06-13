<?php


namespace App\V1\Validators\Cart;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class UpdateCartAdminValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'       => 'required',
            'session_id' => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'id'  => Message::get("carts"),
            'session_id'  => Message::get("carts"),
        ];
    }
}
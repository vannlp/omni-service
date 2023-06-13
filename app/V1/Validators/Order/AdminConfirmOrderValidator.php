<?php
/**
 * User: dai.ho
 * Date: 3/06/2020
 * Time: 10:49 AM
 */

namespace App\V1\Validators\Order;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class AdminConfirmOrderValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'user_id' => 'required',
            'session_id' => 'required',
            'id' => 'required',
            'cart_id' => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'user_id' => Message::get('user_id'),
            'session_id' => Message::get('session_id'),
            'id' => Message::get('id'),
            'cart_id' => Message::get('cart_id')
        ];
    }
}
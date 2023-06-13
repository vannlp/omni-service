<?php
/**
 * Created by PhpStorm.
 * User: dai.ho
 * Date: 10/14/2019
 * Time: 10:35 AM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class UserStatusOrderUpsertValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'user_id'  => 'required|exists:users,id,deleted_at,NULL',
            'order_id' => 'required|exists:orders,id,deleted_at,NULL',
            'status'   => 'required|in:NEW,RECEIVED,IN PROGRESS,COMPLETED,CANCELED,RETURNED',
        ];
    }

    protected function attributes()
    {
        return [
            'user_id'  => Message::get("user_id"),
            'order_id' => Message::get("order_id"),
            'status'   => Message::get("status"),
        ];
    }
}
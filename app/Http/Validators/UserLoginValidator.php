<?php
/**
 * User: Administrator
 * Date: 10/10/2018
 * Time: 08:17 PM
 */

namespace App\Http\Validators;

use App\Supports\Message;

/**
 * Class UserLoginValidator
 * @package App\Http\Validators
 */
class UserLoginValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'device_id'   => 'required',
            'device_type' => 'required',
            'phone'       => 'required|exists:users,phone',
            'password'    => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'phone'        => Message::get("phone"),
            'device_id'    => Message::get("device_id"),
            'device_type'  => Message::get("device_type"),
            'password'     => Message::get("password"),
            'device_token' => Message::get("device_token"),
        ];
    }
}
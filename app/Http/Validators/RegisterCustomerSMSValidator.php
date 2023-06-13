<?php
/**
 * User: dai.ho
 * Date: 11/18/2019
 * Time: 10:36 AM
 */

namespace App\Http\Validators;


use App\Supports\Message;

class RegisterCustomerSMSValidator extends ValidatorBase
{
    protected function attributes()
    {
        return [
            'store_token'       => Message::get("token"),
            'name'              => Message::get("alternative_name"),
            'gender'            => Message::get("gender"),
            'birthday'          => Message::get("birthday"),
            'phone'             => Message::get("phone"),
            'temp_address'      => Message::get("temp_address"),
            'register_city'     => Message::get("register_city"),
            'register_district' => Message::get("register_district"),
            'work_experience'   => Message::get("work_experience"),
            'introduce_from'    => Message::get("introduce_from"),
            'email'             => Message::get("email"),
            'device_token'      => Message::get("device_token"),
            'device_type'       => Message::get("device_type"),
        ];
    }

    protected function rules()
    {
        return [
            'store_token'       => 'required|exists:stores,token,deleted_at,NULL',
            'name'              => 'required|min:5|max:40',
            'gender'            => 'required|in:M,F',
            'birthday'          => 'required|date_format:Y-m-d',
            'phone'             => 'required|numeric|not_in:0|unique:users,phone',
            'temp_address'      => 'required',
            'register_city'     => 'required',
            'register_district' => 'required',
            'work_experience'   => 'required',
            'introduce_from'    => 'required',
            'email'             => 'nullable|email|unique:users,email',
            'device_token'      => 'required',
            'device_type'       => 'required|in:DESKTOP,TABLE,PHONE,ANDROID,IOS,MOBILEWEB',
        ];
    }
}
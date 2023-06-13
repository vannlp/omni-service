<?php
/**
 * User: Administrator
 * Date: 10/10/2018
 * Time: 07:40 PM
 */

namespace App\Http\Validators;


use App\Supports\Message;

class RegisterValidator extends ValidatorBase
{
    protected function attributes()
    {
        return [
            'store_token'   => Message::get("token"),
            'phone'         => Message::get("phone"),
            'device_id'     => Message::get("device_id"),
            'device_type'   => Message::get("device_type"),
            'name'          => Message::get("name"),
            'type'          => Message::get("type"),
            'city_code'     => Message::get("city_code"),
            'district_code' => Message::get("district_code"),
            'ward_code'     => Message::get("ward_code"),
            'password'      => Message::get("password"),
            'ref_code'      => Message::get("ref_code"),
            'register_city' => Message::get("register_city"),
        ];
    }

    protected function rules()
    {
        return [
            'store_token'   => 'required|exists:stores,token,deleted_at,NULL',
            'phone'         => 'required|numeric|not_in:0|unique:users,phone',
            'name'          => 'required|min:5|max:40',
            'type'          => 'required|in:USER,CUSTOMER,PARTNER',
            'city_code'     => 'exists:cities,code',
            'district_code' => 'exists:districts,code',
            'ward_code'     => 'exists:wards,code',
            'password'      => 'required|min:8',
            'register_city' => 'exists:master_data,code,deleted_at,NULL',
        ];
    }


}
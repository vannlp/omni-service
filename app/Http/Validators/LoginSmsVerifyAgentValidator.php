<?php


namespace App\Http\Validators;


use App\Supports\Message;

class LoginSmsVerifyAgentValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'name'          => 'required',
            'gender'        => 'nullable|in:M,F,O',
            'birthday'      => 'required|date_format:Y-m-d',
            'password'      => 'required',
            'id_number'     => 'required',
            'address'       => 'required',
//            'marital_status' => 'required|numeric',
            'city_code'     => 'required|exists:cities,code,deleted_at,NULL',
            'district_code' => 'required|exists:districts,code,deleted_at,NULL',
            'ward_code'     => 'required|exists:wards,code,deleted_at,NULL',
//            'education'      => 'required',
            'group_id'      => 'required',
            'area_id'       => 'required|exists:areas,id,deleted_at,NULL'
        ];
    }

    protected function attributes()
    {
        return [
            'email'          => Message::get("email"),
            'password'       => Message::get("password"),
            'birthday'       => Message::get("birthday"),
            'gender'         => Message::get("gender"),
            'id_number'      => Message::get("id_number"),
            'device_token'   => Message::get("device_token"),
            'address'        => Message::get("address"),
            'marital_status' => Message::get("marital_status"),
            'city_code'      => Message::get("cities"),
            'district_code'  => Message::get("districts"),
            'ward_code'      => Message::get("wards"),
            'education'      => Message::get("education"),
            'area_id'        => Message::get("area_id")
        ];
    }
}
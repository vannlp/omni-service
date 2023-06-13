<?php
/**
 * User: kpistech2
 * Date: 2020-08-02
 * Time: 16:56
 */

namespace App\Http\Validators;


use App\Supports\Message;

class CustomerRegisterValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'name'           => 'required',
            'gender'         => 'nullable|in:M,F,O',
            'birthday'       => 'nullable|date_format:Y-m-d',
            'password'       => 'required',
            'id_number'      => 'nullable',
            'address'        => 'nullable',
            'marital_status' => 'nullable|numeric',
            'city_code'      => 'required|exists:cities,code,deleted_at,NULL',
            'district_code'  => 'required|exists:districts,code,deleted_at,NULL',
            'ward_code'      => 'required|exists:wards,code,deleted_at,NULL',
            'education'      => 'nullable',
            'store_token'    => 'required'
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
            'education'      => Message::get("education")
        ];
    }
}
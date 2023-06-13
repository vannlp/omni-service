<?php


namespace App\Http\Validators;


use App\Supports\Message;

class RegisterPartnerValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'reference_phone' => 'required',
            'name'            => 'required',
            'email'           => 'required',
            'phone'           => 'required',
            'password'        => 'required',
            'id_number'       => 'required',
            'id_images'       => 'required',
            'address'         => 'nullable',
            'city_code'       => 'nullable|exists:cities,code,deleted_at,NULL',
            'district_code'   => 'nullable|exists:districts,code,deleted_at,NULL',
            'ward_code'       => 'nullable|exists:wards,code,deleted_at,NULL',
            'store_token'     => 'required',
            'group_id'        => 'nullable|exists:user_groups,id,deleted_at,NULL',
            //
            //            'gender'         => 'nullable|in:M,F,O',
            //            'birthday'       => 'required|date_format:Y-m-d',
            //            'est_revenues'   => 'required',
            //            'account_type'   => 'required',
            //            'occupation'     => 'required',
            //            'marital_status' => 'required',
            //            'education'      => 'required',
            //            'group_id'       => 'required',
            //            'area_id'         => 'required|exists:areas,id,deleted_at,NULL',
            //            'device_token'    => 'required',
            //            'device_type'     => 'required',
            //            'device_id'       => 'required'
        ];
    }

    protected function attributes()
    {

        return [
            'phone'           => Message::get("phone"),
            'email'           => Message::get("email"),
            'password'        => Message::get("password"),
            'device_token'    => Message::get("device_token"),
            'device_type'     => Message::get("device_type"),
            'device_id'       => Message::get("device_id"),
            'name'            => Message::get("name"),
            'city_code'       => Message::get("cities"),
            'address'         => Message::get("address"),
            'ward_code'       => Message::get("wards"),
            'district_code'   => Message::get("districts"),
            'gender'          => Message::get("gender"),
            'birthday'        => Message::get("birthday"),
            'id_number'       => Message::get("id_number"),
            'id_images'       => Message::get("id_images"),
            'account_type'    => Message::get("type"),
            'occupation'      => Message::get("occupation"),
            'marital_status'  => Message::get("marital_status"),
            'education'       => Message::get("education"),
            'group_id'        => Message::get("group_id"),
            'area_id'         => Message::get("area"),
            'store_token'     => Message::get("stores"),
            'reference_phone' => Message::get("reference_phone"),
        ];
    }
}
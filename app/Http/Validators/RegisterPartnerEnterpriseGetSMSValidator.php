<?php
/**
 * User: dai.ho
 * Date: 11/18/2019
 * Time: 10:36 AM
 */

namespace App\Http\Validators;


use App\Supports\Message;

class RegisterPartnerEnterpriseGetSMSValidator extends ValidatorBase
{
    protected function attributes()
    {
        return [
            'name'              => Message::get("alternative_name"),
            'gender'            => Message::get("gender"),
            'birthday'          => Message::get("birthday"),
            'phone'             => Message::get("phone"),
            'register_city'     => Message::get("register_city"),
            'register_district' => Message::get("register_district"),
            'work_experience'   => Message::get("work_experience"),
            'introduce_from'    => Message::get("introduce_from"),
            'email'             => Message::get("email"),
            'device_token'      => Message::get("device_token"),
            'device_type'       => Message::get("device_type"),
            'representative'    => Message::get("representative"),
            'operation_field'   => Message::get("operation_field"),
            'area_id'           => Message::get("areas"),
        ];
    }

    protected function rules()
    {
        return [
            'name'                => 'required|min:5|max:40',
            'gender'              => 'required|in:M,F',
            'birthday'            => 'required|date_format:Y-m-d',
            'phone'               => 'required|numeric|not_in:0',
            'representative'      => 'required',
            'operation_field'     => 'required',
            'register_city'       => 'required|exists:cities,id,deleted_at,NULL',
            'register_district'   => 'required',
//            'register_district'   => 'required|array',
            'register_district.*' => 'required|exists:districts,id,deleted_at,NULL',
            'work_experience'     => 'nullable|in:0_1,1_2,2_n',
            'introduce_from'      => 'required|in:SHINER,ADS,FRIEND,TV,LEAFLET,FACEBOOK,INSTAGRAM,ZALO,ADS-INTERNET,OTHER',
            'email'               => 'nullable|email|unique:users,email',
            'device_token'        => 'required',
            'device_type'         => 'required|in:DESKTOP,TABLE,PHONE,ANDROID,IOS,MOBILEWEB',
            'lat'                 => 'required',
            'long'                => 'required',
            'area_id'             => 'required|exists:areas,id,deleted_at,NULL',
        ];
    }
}
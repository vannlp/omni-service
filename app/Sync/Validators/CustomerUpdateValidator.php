<?php
/**
 * User: dai.ho
 * Date: 28/01/2021
 * Time: 6:24 PM
 */

namespace App\Sync\Validators;


use App\Supports\Message;
use App\TM;
use App\User;

class CustomerUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'email'           => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $item = User::where('email', $value)
                        ->where('company_id', TM::getIDP()->company_id)
                        ->where('store_id', TM::getIDP()->store_id)
                        ->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                },
            ],
            'phone'           => [
                'required',
                'max:12',
                function ($attribute, $value, $fail) {
                    if ($value != 'VT_NOT_PHONE') {
                        $item = User::where('phone', $value)
                            ->where('company_id', TM::getIDP()->company_id)
                            ->where('store_id', TM::getIDP()->store_id)
                            ->whereNull('deleted_at')->get()->toArray();
                        if (!empty($item) && count($item) > 0) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                },
            ],
            'reference_phone' => [
                'nullable',
                'max:12',
                function ($attribute, $value, $fail) {
                    $item = User::where('phone', $value)
                        ->where('company_id', TM::getIDP()->company_id)
                        ->where('store_id', TM::getIDP()->store_id)
                        ->whereNull('deleted_at')->get()->toArray();
                    if (empty($item)) {
                        return $fail(Message::get("V003", "$attribute: #$value"));
                    }
                },
            ],
            'code'            => [
                'required',
//                function ($attribute, $value, $fail) {
//                    $value = string_to_slug($value);
//                    $item  = User::where('code', $value)
//                        ->where('company_id', TM::getIDP()->company_id)
//                        ->where('store_id', TM::getIDP()->store_id)
//                        ->whereNull('deleted_at')->get()->toArray();
//                    if (!empty($item) && count($item) > 0) {
//                        return $fail(Message::get("unique", "$attribute: #$value"));
//                    }
//                },
            ],
            //            'reference_phone' => 'nullable|exists:users,phone,deleted_at,NULL',
            'name'            => 'required',
            'address'         => 'required',
            'city_code'       => 'required|exists:cities,code,deleted_at,NULL',
            'district_code'   => 'required|exists:districts,code,deleted_at,NULL',
            'ward_code'       => 'required|exists:wards,code,deleted_at,NULL',
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
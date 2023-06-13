<?php
/**
 * User: dai.ho
 * Date: 28/01/2021
 * Time: 6:23 PM
 */

namespace App\Sync\Validators;


use App\Supports\Message;
use App\TM;
use App\User;

class DistributorUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'email'                  => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $item = User::where('email', $value)
                        ->where('company_id', TM::getIDP()->company_id)
                        ->where('store_id', TM::getIDP()->store_id)
                        ->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 1) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                },
            ],
            'Phone'                  => [
                'required',
                'max:12',
                function ($attribute, $value, $fail) {
                    $item = User::where('phone', $value)
                        ->where('company_id', TM::getIDP()->company_id)
                        ->where('store_id', TM::getIDP()->store_id)
                        ->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 1) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                },
            ],
            'code'                   => [
                'required',
                function ($attribute, $value, $fail) {
                    $value = string_to_slug($value);
                    $item = User::where('code', $value)
                        ->where('company_id', TM::getIDP()->company_id)
                        ->where('store_id', TM::getIDP()->store_id)
                        ->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 11) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                },
            ],
            'name'                   => 'required',
            'register_at'            => 'date_format:Y-m-d',
            'start_work_at'          => 'date_format:Y-m-d',
            'companies'              => 'nullable|array',
            'companies.*.company_id' => 'required|exists:companies,id,deleted_at,NULL',
            'companies.*.role_id'    => 'required|exists:roles,id,deleted_at,NULL',
            'stores'                 => 'nullable|array',
            'stores.*.store_id'      => 'required|exists:stores,id,deleted_at,NULL',
            'stores.*.role_id'       => 'required|exists:roles,id,deleted_at,NULL',
            // 'group_id'               => 'nullable|exists:user_groups,id,deleted_at,NULL',
            'birthday'               => 'nullable|date_format:Y-m-d',
            'marital_status'         => 'nullable|numeric',
            'city_code'              => 'required|exists:cities,code,deleted_at,NULL',
            'district_code'          => 'required|exists:districts,code,deleted_at,NULL',
            'ward_code'              => 'required|exists:wards,code,deleted_at,NULL',
            'address'                => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'phone'                  => Message::get("phone"),
            'name'                   => Message::get("name"),
            'register_at'            => Message::get("register_at"),
            'start_work_at'          => Message::get("start_work_at"),
            'companies'              => Message::get("companies"),
            'companies.*.company_id' => Message::get("companies"),
            'companies.*.role_id'    => Message::get("roles"),
            'stores'                 => Message::get("stores"),
            'stores.*.store_id'      => Message::get("stores"),
            'stores.*.role_id'       => Message::get("roles"),
            'email'                  => Message::get("email"),
            'birthday'               => Message::get("birthday"),
            'marital_status'         => Message::get("marital_status"),
            'city_code'              => Message::get("cities"),
            'district_code'          => Message::get("districts"),
            'ward_code'              => Message::get("wards"),
            'address'                => Message::get("address"),
        ];
    }
}
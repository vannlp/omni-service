<?php

/**
 * User: Administrator
 * Date: 28/09/2018
 * Time: 09:35 PM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use App\User;
use Illuminate\Http\Request;

class UserCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                     => 'exists:users,id,deleted_at,NULL',
            'email'                  => 'nullable|unique_create_company_delete:users,email',
            'phone'                  => [
                'required',
                'max:12',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    $item = User::where('phone', $value)->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        if (count($item) > 1 || ($input['id'] > 0 && $item[0]['id'] != $input['id'])) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                },
            ],
            //            'code'                   => 'required|unique:users,code',
            'code'                   => 'required|unique_create_company_delete:users,code',
            'password'               => 'required',
            'name'                   => 'required',
            'register_at'            => 'date_format:Y-m-d',
            'start_work_at'          => 'date_format:Y-m-d',
            'type'                   => 'required|in:USER,CUSTOMER,PARTNER,ENTERPRISE,AGENT',
            'area_id'                => 'nullable|exists:areas,id,deleted_at,NULL',
            'companies'              => 'nullable|array',
            'zone_hub_ids'           => 'nullable|array',
            'company_id'             => 'nullable|exists:companies,id,deleted_at,NULL',
            'store_id'               => 'nullable|exists:stores,id,deleted_at,NULL',
            'companies.*.company_id' => 'required|exists:companies,id,deleted_at,NULL',
            'companies.*.role_id'    => 'required|exists:roles,id,deleted_at,NULL',
            'register_areas'                              => 'nullable|array',
            'register_areas.*.city_code'                  => 'required',
            'register_areas.*.city_name'                  => 'required',
            'register_areas.*.district_code'              => 'required',
            'register_areas.*.district_name'              => 'required',
            'register_areas.*.ward_code'                  => 'required',
            'register_areas.*.ward_name'                  => 'required',
            'stores'                 => 'nullable|array',
            'stores.*.store_id'      => 'required|exists:stores,id,deleted_at,NULL',
            'stores.*.role_id'       => 'required|exists:roles,id,deleted_at,NULL',
            //'group_id'               => 'nullable|exists:user_groups,id,deleted_at,NULL',
            'group_code'             => 'nullable|exists:user_groups,code,deleted_at,NULL',
            'distributor_id'         => 'nullable|exists:users,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'phone'                  => Message::get("phone"),
            'username'               => Message::get("username"),
            'email'                  => Message::get("email"),
            'name'                   => Message::get("name"),
            'distributor_id'         => Message::get("distributor_id"),
            'area_id'                => Message::get("area_id"),
            'password'               => Message::get("password"),
            'zone_hub_ids'           => Message::get("zone_hub_ids"),
            'register_at'            => Message::get("register_at"),
            'start_work_at'          => Message::get("start_work_at"),
            'type'                   => Message::get("type"),
            'company_id'             => Message::get("companies"),
            'companies'              => Message::get("companies"),
            'companies.*.company_id' => Message::get("companies"),
            'companies.*.role_id'    => Message::get("roles"),
            'store_id'               => Message::get("stores"),
            'areas'                  => "areas",
            'stores'                 => Message::get("stores"),
            'stores.*.store_id'      => Message::get("stores"),
            'stores.*.role_id'       => Message::get("roles"),
        ];
    }
}

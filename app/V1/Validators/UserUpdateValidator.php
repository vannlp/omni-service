<?php

/**
 * User: Administrator
 * Date: 14/10/2018
 * Time: 01:30 PM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use App\User;
use Illuminate\Http\Request;

class UserUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                     => 'required|exists:users,id,deleted_at,NULL',
            'email'                  => 'nullable',
            //            'code'                   => [
            //                'required',
            //                'max:50',
            //                function ($attribute, $value, $fail) {
            //                    $input = Request::capture();
            //                    $item  = User::where('code', $value)->whereNull('deleted_at')->get()->toArray();
            //                    if (!empty($item) && count($item) > 0) {
            //                        if (count($item) > 1 || ($input['id'] > 0 && $item[0]['id'] != $input['id'])) {
            //                            return $fail(Message::get("unique", "$attribute: #$value"));
            //                        }
            //                    }
            //                },
            //            ],
            'code'                   => 'required',
            'username'               => 'nullable|max:50',
            'password'               => 'nullable|min:8',
            'phone'                  => 'max:14',
            'partner_type'           => 'nullable|in:PERSONAL,ENTERPRISE',
            'area_id'                => 'nullable|exists:areas,id,deleted_at,NULL',
            'companies'              => 'nullable|array',
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
            'group_id'               => 'nullable|exists:user_groups,id,deleted_at,NULL',
            'distributior_id'        => 'nullable|exists:users,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'phone'                  => Message::get("phone"),
            'email'                  => Message::get("email"),
            'code'                   => Message::get("code"),
            'username'               => Message::get("username"),
            'name'                   => Message::get("name"),
            'distributor_id'         => Message::get("distributor_id"),
            'area_id'                => Message::get("area_id"),
            'password'               => Message::get("password"),
            'partner_type'           => Message::get("partner_type"),
            'company_id'             => Message::get("companies"),
            'companies'              => Message::get("companies"),
            'companies.*.company_id' => Message::get("companies"),
            'companies.*.role_id'    => Message::get("roles"),
            'stores'                 => Message::get("stores"),
            'stores.*.store_id'      => Message::get("stores"),
            'stores.*.role_id'       => Message::get("roles"),
        ];
    }
}

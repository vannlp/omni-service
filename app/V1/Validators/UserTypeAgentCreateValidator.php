<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use App\User;
use Illuminate\Http\Request;

class UserTypeAgentCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                     => 'exists:users,id,deleted_at,NULL',
            'email'                  => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    $item = User::where('email', $value)->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        if (count($item) > 1 || ($input['id'] > 0 && $item[0]['id'] != $input['id'])) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                },
            ],
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
            'code'                   => 'required|unique:users,code',
            'password'               => 'required',
            'name'                   => 'required',
            'register_at'            => 'date_format:Y-n-j',
            'start_work_at'          => 'date_format:Y-n-j',
            'type'                   => 'required|in:USER,CUSTOMER,PARTNER,ENTERPRISE,AGENT',
            'company_id'             => 'nullable|exists:companies,id,deleted_at,NULL',
            'store_id'               => 'nullable|exists:stores,id,deleted_at,NULL',
            'companies'              => 'nullable|array',
            'companies.*.company_id' => 'required|exists:companies,id,deleted_at,NULL',
            'companies.*.role_id'    => 'required|exists:roles,id,deleted_at,NULL',
            'stores'                 => 'nullable|array',
            'stores.*.store_id'      => 'required|exists:stores,id,deleted_at,NULL',
            'stores.*.role_id'       => 'required|exists:roles,id,deleted_at,NULL',
            // 'group_id'               => 'nullable|exists:user_groups,id,deleted_at,NULL',
            'group_code'             => 'required|exists:user_groups,code,deleted_at,NULL',
            'gender'                 => 'required|in:M,F,O',
            'birthday'               => 'required|date_format:Y-m-d',
            'indentity_card'         => 'required',
            'occupation'             => 'required',
            'marital_status'         => 'required|numeric',
            'city_code'              => 'required|exists:cities,code,deleted_at,NULL',
            'district_code'          => 'required|exists:districts,code,deleted_at,NULL',
            'ward_code'              => 'required|exists:wards,code,deleted_at,NULL',
            'address'                => 'required',
            'education'              => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'phone'                  => Message::get("phone"),
            'username'               => Message::get("username"),
            'name'                   => Message::get("name"),
            'register_at'            => Message::get("register_at"),
            'start_work_at'          => Message::get("start_work_at"),
            'type'                   => Message::get("type"),
            'company_id'             => Message::get("companies"),
            'companies'              => Message::get("companies"),
            'companies.*.company_id' => Message::get("companies"),
            'companies.*.role_id'    => Message::get("roles"),
            'store_id'               => Message::get("stores"),
            'stores'                 => Message::get("stores"),
            'stores.*.store_id'      => Message::get("stores"),
            'stores.*.role_id'       => Message::get("roles"),
            'email'                  => Message::get("email"),
            'password'               => Message::get("password"),
            'birthday'               => Message::get("birthday"),
            'gender'                 => Message::get("gender"),
            'indentity_card'         => Message::get("indentity_card"),
            'device_token'           => Message::get("device_token"),
            'occupation'             => Message::get("occupation"),
            'marital_status'         => Message::get("marital_status"),
            'city_code'              => Message::get("cities"),
            'district_code'          => Message::get("districts"),
            'ward_code'              => Message::get("wards"),
            'address'                => Message::get("address"),
            'education'              => Message::get("education")
        ];
    }
}
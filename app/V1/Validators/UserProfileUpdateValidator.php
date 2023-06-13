<?php
/**
 * User: kpistech2
 * Date: 2019-04-10
 * Time: 23:03
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Profile;
use App\Supports\Message;
use Illuminate\Http\Request;

class UserProfileUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'user_id'       => 'required|exists:users,id,deleted_at,NULL',
            'email'         => 'nullable',
            // [
            //     'nullable',
            //     function ($attribute, $value, $fail) {
            //         $input = Request::capture();
            //         $item = Profile::model()->where('email', $value)->get()->toArray();
            //         if (!empty($item) && count($item) > 0) {
            //             if (count($item) > 1 || ($input['user_id'] > 0 && $item[0]['user_id'] != $input['user_id'])) {
            //                 return $fail(Message::get("unique", "$attribute: #$value"));
            //             }
            //         }
            //     },
            // ],
            'first_name'    => 'nullable|max:100',
            'last_name'     => 'nullable|max:100',
            'address'       => 'nullable|max:500',
            'zone_hub_ids'  => 'nullable|array',
            'birthday'      => 'nullable',
            'password'      => 'nullable|min:8',
            'phone'         => 'nullable|max:12',
            'city_code'     => 'exists:cities,code,deleted_at,NULL',
            'district_code' => 'exists:districts,code,deleted_at,NULL',
            'ward_code'     => 'exists:wards,code,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'user_id'       => Message::get("users"),
            'phone'         => Message::get("phone"),
            'email'         => Message::get("email"),
            'zone_hub_ids'  => Message::get("zone_hub_ids"),
            'first_name'    => Message::get("alternative_name"),
            'last_name'     => Message::get("alternative_name"),
            'password'      => Message::get("password"),
            'address'       => Message::get("address"),
            'birthday'      => Message::get("birthday"),
            'city_code'     => Message::get("city_code"),
            'district_code' => Message::get("district_code"),
            'ward_code'     => Message::get("ward_code"),
        ];
    }
}

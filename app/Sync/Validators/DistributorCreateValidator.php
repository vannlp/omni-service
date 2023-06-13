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

class DistributorCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'Email'        => [
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
            'Phone'        => [
                'nullable',
                'max:12',
                function ($attribute, $value, $fail) {
                    $item = User::where('phone', $value)
                        ->where('company_id', TM::getIDP()->company_id)
                        ->where('store_id', TM::getIDP()->store_id)
                        ->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                },
            ],
            'ShopCode'     => [
                'required',
                function ($attribute, $value, $fail) {
                    $value = string_to_slug($value);
                    $item = User::where('code', $value)
                        ->where('company_id', TM::getIDP()->company_id)
                        ->where('store_id', TM::getIDP()->store_id)
                        ->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                },
            ],
            'CustomerCode' => [
                'required',
                function ($attribute, $value, $fail) {
                    $value = string_to_slug($value);
                    $item = User::where('code', $value)
                        ->where('company_id', TM::getIDP()->company_id)
                        ->where('store_id', TM::getIDP()->store_id)
                        ->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                },
            ],
            'Lat'          => 'nullable',
            'Lng'          => 'nullable',
            'ShortCode'    => 'nullable',
            'CustomerName' => 'required',
            'Status'       => 'required|numeric|max:1',
            'Province'     => 'nullable|exists:cities,province,deleted_at,NULL',
            'District'     => 'nullable|exists:districts,district,deleted_at,NULL',
            'Ward'         => 'nullable|exists:wards,name,deleted_at,NULL',
            'Address'      => 'nullable',
        ];
    }

    protected function attributes()
    {
        return [
            'ShopCode'     => Message::get("code"),
            'ShortCode'    => Message::get("code"),
            'Phone'        => Message::get("phone"),
            'CustomerName' => Message::get("name"),
            'Email'        => Message::get("email"),
            'Province'     => Message::get("cities"),
            'District'     => Message::get("districts"),
            'Ward'         => Message::get("wards"),
            'Address'      => Message::get("address"),
        ];
    }
}
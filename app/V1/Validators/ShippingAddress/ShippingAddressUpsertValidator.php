<?php


namespace App\V1\Validators\ShippingAddress;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ShippingAddressUpsertValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'             => 'nullable|exists:shipping_address,id,deleted_at,NULL',
            'full_name'      => 'required',
            'phone'          => 'required|max:14',
            'city_code'      => 'required|exists:cities,code,deleted_at,NULL',
            'district_code'  => 'required|exists:districts,code,deleted_at,NULL',
            'ward_code'      => 'required|exists:wards,code,deleted_at,NULL',
            'street_address' => 'required',
            'is_default'     => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'full_name'      => Message::get("alternative_name"),
            'phone'          => Message::get("phone"),
            'city_code'      => Message::get("cities"),
            'district_code'  => Message::get("districts"),
            'ward_code'      => Message::get("wards"),
            'street_address' => Message::get("address"),
            'is_default'     => Message::get("is_default"),
        ];
    }
}
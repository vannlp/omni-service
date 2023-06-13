<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Order;
use App\Supports\Message;

class OrderCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'order_type'                     => 'nullable|in:' . implode(",", array_keys(ORDER_TYPE_NAME)),
            'status'                         => 'exists:order_status,code,deleted_at,NULL',
            'phone'                          => 'max:14',
            'customer_id'                    => 'exists:users,id,deleted_at,NULL',
            'partner_id'                     => 'exists:users,id,deleted_at,NULL',
            'store_id'                       => 'required|exists:stores,id,deleted_at,NULL',
            'shipping_address_id'            => 'integer',
//            'shipping_address'               => 'required',
            'delivery_time'                  => 'required',
//            'street_address'                 => 'required',
            'shipping_address_ward_code'     => 'nullable|exists:wards,code,deleted_at,NULL',
            'shipping_address_district_code' => 'nullable|exists:districts,code,deleted_at,NULL',
            'shipping_address_city_code'     => 'nullable|exists:cities,code,deleted_at,NULL',
//            'shipping_address_phone'         => 'required',
//            'shipping_address_full_name'     => 'required',
            //            'geometry'             => 'required|array',
            //            'geometry.latitude'    => 'required',
            //            'geometry.longitude'   => 'required',
            //            'district_code'        => 'required',
            'image_uploads'                  => 'nullable|array',
            'image_uploads.*'                => 'required',
            'images'                         => 'nullable|array',
            'images.*.id'                    => 'required|integer',
            'images.*.url'                   => 'required',
            'details'                        => 'required|array',
            'details.*.product_id'           => 'required|exists:products,id,deleted_at,NULL',
            'details.*.qty'                  => 'required|numeric',
            'details.*.price'                => 'required|numeric',
            'details.*.total'                => 'required|numeric',
        ];
    }

    protected function attributes()
    {
        return [
            'code'                           => Message::get("code"),
            'phone'                          => Message::get("phone"),
            'status'                         => Message::get("status"),
            'customer_id'                    => Message::get("customer_id"),
            'partner_id'                     => Message::get("partner_id"),
            'shipping_address_id'            => Message::get("shipping_address_id"),
            'street_address'                 => Message::get("street_address"),
            'shipping_address_ward_code'     => Message::get("wards"),
            'shipping_address_district_code' => Message::get("districts"),
            'shipping_address_city_code'     => Message::get("cities"),
            'shipping_address_phone'         => Message::get("shipping_address_phone"),
            'shipping_address_full_name'     => Message::get("shipping_address_full_name"),
            'shipping_address'               => Message::get("shipping_address"),
            'delivery_time'                  => Message::get("delivery_time"),
            'district_code'                  => Message::get("districts"),
            'store_id'                       => Message::get("stores"),
            'details'                        => Message::get("detail"),
            'details.*.qty'                  => Message::get("quantity"),
            'details.*.price'                => Message::get("price"),
            'details.*.product_id'           => Message::get("product_id"),
            'details.*.total'                => Message::get("total"),
        ];
    }
}
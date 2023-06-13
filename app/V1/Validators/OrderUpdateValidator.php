<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Order;
use App\Supports\Message;
use Illuminate\Http\Request;

class OrderUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                             => 'required|exists:orders,id,deleted_at,NULL',
            'code'                           => 'nullable|unique_update:orders',
            'order_type'                     => 'nullable|in:' . implode(",", array_keys(ORDER_TYPE_NAME)),
            'status'                         => 'exists:order_status,code,deleted_at,NULL',
            'customer_id'                    => 'exists:users,id,deleted_at,NULL',
            'partner_id'                     => 'exists:users,id,deleted_at,NULL',
            'store_id'                       => 'nullable|exists:stores,id,deleted_at,NULL',
            'shipping_address_id'            => 'integer',
            'shipping_address'               => 'nullable',
//            'street_address'                 => 'required',
            'shipping_address_ward_code'     => 'nullable|exists:wards,code,deleted_at,NULL',
            'shipping_address_district_code' => 'nullable|exists:districts,code,deleted_at,NULL',
            'shipping_address_city_code'     => 'nullable|exists:cities,code,deleted_at,NULL',
//            'shipping_address_phone'         => 'required',
//            'shipping_address_full_name'     => 'required',
            //            'geometry'             => 'required|array',
            //            'geometry.latitude'    => 'required',
            //            'geometry.longitude'   => 'required',
            'image_uploads'                  => 'nullable|array',
            'image_uploads.*'                => 'required',
            'images'                         => 'nullable|array',
            'images.*.id'                    => 'required|integer',
            'images.*.url'                   => 'required',
            'details'                        => 'required|nullable|array',
            'details.*.product_id'           => 'nullable|exists:products,id,deleted_at,NULL',
            'details.*.qty'                  => 'nullable|numeric',
            'details.*.price'                => 'nullable|numeric',
            'details.*.total'                => 'nullable|numeric',
        ];
    }

    protected function attributes()
    {
        return [
            'code'                           => Message::get("code"),
            'status'                         => Message::get("status"),
            'customer_id'                    => Message::get("customer_id"),
            'partner_id'                     => Message::get("partner_id"),
            'shipping_address_id'            => Message::get("shipping_address_id"),
            'shipping_address'               => Message::get("shipping_address"),
            'street_address'                 => Message::get("street_address"),
            'shipping_address_ward_code'     => Message::get("shipping_address_ward_code"),
            'shipping_address_district_code' => Message::get("shipping_address_district_code"),
            'shipping_address_city_code'     => Message::get("shipping_address_city_code"),
            'shipping_address_phone'         => Message::get("shipping_address_phone"),
            'shipping_address_full_name'     => Message::get("shipping_address_full_name"),
            'store_id'                       => Message::get("stores"),
            'details'                        => Message::get("detail"),
            'details.*.qty'                  => Message::get("quantity"),
            'details.*.price'                => Message::get("price"),
            'details.*.product_id'           => Message::get("product_id"),
            'details.*.total'                => Message::get("total"),
        ];
    }
}
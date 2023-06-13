<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ShipOrderUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                     => 'required|exists:ship_orders,id,deleted_at,NULL',
            'code'                   => 'nullable',
            'customer_id'            => 'required',
            'order_id'               => 'exists:orders,id,deleted_at,NULL',
            'order_code'             => 'exists:orders,code,deleted_at,NULL',
            'status'                 => 'required',
            'store_id'               => 'required',
            'customer_name'          => 'max:500',
            'customer_address'       => 'max:300',
            'customer_phone'         => 'max:15',
            'approver'               => 'required|exists:users,id,deleted_at,NULL',
            'details'                => 'required|array',
            'details.*.product_id'   => 'required|exists:products,id,deleted_at,NULL',
            'details.*.batch_id'     => 'required|exists:batches,id,deleted_at,NULL',
            'details.*.qty'          => 'required',
            'details.*.price'        => 'nullable|numeric',
            'details.*.warehouse_id' => 'nullable|exists:warehouses,id,deleted_at,NULL',
            'details.*.ship_qty'     => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'code'                   => Message::get("code"),
            'customer_id'            => Message::get("customer_id"),
            'store_id'               => Message::get("store_id"),
            'order_id'               => Message::get("order_id"),
            'order_code'             => Message::get("order_code"),
            'customer_name'          => Message::get("customer_name"),
            'customer_address'       => Message::get("customer_address"),
            'customer_phone'         => Message::get("customer_phone"),
            'approver'               => Message::get("approver"),
            'status'                 => Message::get("status"),
            'details'                => Message::get("detail"),
            'details.*.qty'          => Message::get("quantity"),
            'details.*.price'        => Message::get("price"),
            'details.*.product_id'   => Message::get("product_id"),
            'details.*.warehouse_id' => Message::get("warehouse_id"),
        ];
    }
}
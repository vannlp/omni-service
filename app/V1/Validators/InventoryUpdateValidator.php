<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class InventoryUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'                   => 'nullable',
            'user_id'                => 'required|exists:users,id,deleted_at,NULL',
            'transport'              => 'nullable|max:200',
            'description'            => 'nullable|max:250',
            'date'                   => 'date_format:d-m-Y',
            'status'                 => 'required|max:200',
            'type'                   => 'in:0,1',
            'details'                => 'required|array',
            'details.*.product_id'   => 'required|exists:products,id,deleted_at,NULL',
//            'details.*.warehouse_id' => 'required|exists:warehouses,id,deleted_at,NULL',
            'warehouse_id'           => 'required|exists:warehouses,id,deleted_at,NULL',
            'details.*.unit_id'      => 'required|exists:units,id,deleted_at,NULL',
            'details.*.quantity'     => 'required|numeric',
//            'details.*.batch_id'     => 'required|exists:batches,id,deleted_at,NULL',
            'batch_id'               => 'required|exists:batches,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'code'                   => Message::get("code"),
            'user_id'                => Message::get("user_id"),
            'transport'              => Message::get("transport"),
            'description'            => Message::get("description"),
            'date'                   => Message::get("date"),
            'status'                 => Message::get("status"),
            'type'                   => Message::get("type"),
            'details'                => Message::get("detail"),
            'details.*.product_id'   => Message::get("product_id"),
//            'details.*.warehouse_id' => Message::get("warehouses_id"),
            'warehouse_id'           => Message::get("warehouses_id"),
            'details.*.unit_id'      => Message::get("unit_id"),
            'details.*.quantity'     => Message::get("quantity"),
//            'details.*.batch_id'     => Message::get("batch_id"),
            'batch_id'               => Message::get("batch_id"),
        ];
    }
}
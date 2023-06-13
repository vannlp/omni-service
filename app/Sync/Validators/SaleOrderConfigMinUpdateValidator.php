<?php

namespace App\Sync\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class SaleOrderConfigMinUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'         => 'required|exists:sale_order_config_mins,id,deleted_at,NULL',
            'shop_id'    => 'required',
            'unit_id'    => 'required',
            'product_id' => 'required',
            'quantity'   => 'required|numeric'
        ];
    }

    protected function attributes()
    {
        return [
            'shop_id'    => Message::get("shop_id"),
            'unit_id'    => Message::get("unit_id"),
            'product_id' => Message::get("product_id"),
            'quantity'   => Message::get("quantity")
        ];
    }
}
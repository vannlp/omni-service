<?php

namespace App\V1\Validators\Shop;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class OrderCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'phone'      => 'required',
            'product_id' => 'required',
            'quantity'   => 'required|numeric'
        ];
    }

    protected function attributes()
    {
        return [
            'phone'      => Message::get("phone"),
            'product_id' => Message::get("product_id"),
            'quantity'   => Message::get("quantity")
        ];
    }
}
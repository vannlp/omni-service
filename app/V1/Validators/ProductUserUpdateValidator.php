<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ProductUserUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'product_id' => 'exists:products,id,deleted_at,NULL',
            'user_id'    => 'exists:users,id,deleted_at,NULL',
            //'stock'      => 'required|numeric',
            'total_qty'  => 'required|numeric',
        ];
    }

    protected function attributes()
    {
        return [
            'product_id' => Message::get("products"),
            'user_id'    => Message::get("user_id"),
            //'stock'      => Message::get("stock"),
            'total_qty'  => Message::get("total_qty")
        ];
    }
}
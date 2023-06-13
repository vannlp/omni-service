<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ProductUserCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'user_id'              => 'exists:users,id,deleted_at,NULL',
            'details'              => 'required|array',
            'details.*.product_id' => 'required|exists:products,id,deleted_at,NULL',
            'details.*.total_qty'  => 'required|numeric',
            'details.*.stock'      => 'required|numeric'
        ];
    }

    protected function attributes()
    {
        return [
            'user_id'              => Message::get("user_id"),
            'details'              => Message::get("detail"),
            'details.*.product_id' => Message::get("products"),
            'details.*.total_qty'  => Message::get("total_qty"),
            'details.*.stock'      => Message::get("stock"),
        ];
    }
}
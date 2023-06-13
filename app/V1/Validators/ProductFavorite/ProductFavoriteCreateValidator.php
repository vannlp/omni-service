<?php


namespace App\V1\Validators\ProductFavorite;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ProductFavoriteCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
//            'user_id'    => 'required|exists:users,id,deleted_at,NULL',
            'product_id' => 'required|exists:products,id,deleted_at,NULL'
        ];
    }

    protected function attributes()
    {
        return [
            'user_id'    => Message::get("user_id"),
            'product_id' => Message::get("product_id"),
        ];
    }
}
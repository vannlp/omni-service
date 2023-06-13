<?php

/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:59 PM
 */

namespace App\V1\Validators\ProductHub;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ProductHubUpsertValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'            => 'nullable|exists:product_hubs,id,deleted_at,NULL',
//            'product_id'    => 'required',
//            'user_id'       => 'required|exists:users,id,deleted_at,NULL',
//            'product_code'  => 'required',
//            'product_name'  => 'required',
//            'limit_date'    => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'id'            => Message::get("id"),
            'user_id'       => Message::get("user_id"),
//            'product_id'    => Message::get("product_id"),
//            'product_code'  => Message::get("product_code"),
//            'product_name'  => Message::get("product_name"),
//            'limit_date'    => Message::get("limit_date")
        ];
    }
}

<?php
/**
 * User: dai.ho
 * Date: 3/06/2020
 * Time: 10:49 AM
 */

namespace App\V1\Validators\Order;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class OrderAssignEnterprisesValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'product_id' => 'required|exists:products,id,deleted_at,NULL',
            'qty'        => 'nullable|numeric',
            'price'      => 'nullable|numeric',
            'total'      => 'nullable|numeric',
        ];
    }

    protected function attributes()
    {
        return [
            'product_id' => Message::get("product_id"),
            'qty'        => Message::get("quantity"),
            'price'      => Message::get("price"),
            'total'      => Message::get("total"),
        ];
    }
}
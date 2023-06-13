<?php
/**
 * User: dai.ho
 * Date: 26/02/2021
 * Time: 9:57 AM
 */

namespace App\Sync\Validators;


use App\Supports\Message;

class ShipOrderValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'ORDER_NUMBER'                => 'required|exists:orders,code,deleted_at,NULL',
            'STATUS'                      => 'required',
//            'QUANTITY_APPROVED'           => 'numeric',
//            'QUANTITY_PENDING'            => 'numeric',
            'details'                     => 'required|array',
            'details.*.PRODUCT_CODE'      => 'required|exists:products,code,deleted_at,NULL',
//            'details.*.QUANTITY_APPROVED' => 'numeric',
//            'details.*.QUANTITY_PENDING'  => 'numeric',
        ];
    }

    protected function attributes()
    {
        return [
            'ORDER_NUMBER'                => Message::get("code"),
            'STATUS'                      => Message::get("status"),
            'QUANTITY_APPROVED'           => Message::get("quantity"),
            'QUANTITY_PENDING'            => Message::get("quantity"),
            'details'                     => Message::get("detail"),
            'details.*.QUANTITY_APPROVED' => Message::get("quantity"),
            'details.*.QUANTITY_PENDING'  => Message::get("quantity"),
            'details.*.PRODUCT_CODE'      => Message::get("products"),
        ];
    }
}
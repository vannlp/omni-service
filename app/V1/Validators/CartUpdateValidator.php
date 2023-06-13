<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class CartUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        $paymentMethods = implode(",", array_keys(PAYMENT_METHOD_NAME));
        return [
            'id'                   => 'nullable|exists:carts,id,deleted_at,NULL',
            'address'              => 'nullable|max:255',
            'description'          => 'nullable|max:255',
            'phone'                => 'nullable|max:14',
            'payment_method'       => 'nullable|in:' . $paymentMethods,
            'receiving_time'       => 'nullable',
            'details'              => 'nullable|array',
            'details.*.product_id' => 'nullable|exists:products,id,deleted_at,NULL',
            'details.*.quantity'   => 'nullable|numeric',
        ];
    }

    protected function attributes()
    {
        return [
            'address'              => Message::get("address"),
            'description'          => Message::get("description"),
            'phone'                => Message::get("phone"),
            'payment_method'       => Message::get("payment_method"),
            'receiving_time'       => Message::get("receiving_time"),
            'details'              => Message::get("detail"),
            'details.*.quantity'   => Message::get("quantity"),
            'details.*.product_id' => Message::get("product_id"),
        ];
    }
}
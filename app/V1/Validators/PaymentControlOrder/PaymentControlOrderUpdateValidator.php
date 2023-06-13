<?php
/**
 * User: kpistech2
 * Date: 2020-07-02
 * Time: 22:32
 */

namespace App\V1\Validators\PaymentControlOrder;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PaymentControlOrderUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'order_id'      => 'required|exists:orders,id,deleted_at,NULL',
            'payment_price' => 'required|numeric',
            'payment_type'  => 'nullable|in:CASH,TRANSFER',
            'payment_date'  => 'required|date_format:d-m-Y',
        ];
    }

    protected function attributes()
    {
        return [
            'order_id'      => Message::get("orders"),
            'payment_price' => Message::get("price"),
            'payment_type'  => Message::get("type"),
            'payment_date'  => Message::get("date"),
        ];
    }
}
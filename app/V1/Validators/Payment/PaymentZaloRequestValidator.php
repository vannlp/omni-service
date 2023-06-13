<?php
/**
 * User: kpistech2
 * Date: 2020-11-02
 * Time: 15:14
 */

namespace App\V1\Validators\Payment;


use App\Http\Validators\ValidatorBase;

class PaymentZaloRequestValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            // 'orderId'     => 'required',
            // 'amount'      => 'required|numeric',
//            'type'        => 'required|in:PAYMENT,WITHDRAW,RECHARGE',
            'url' => 'required'
        ];
    }

    protected function attributes()
    {
        return [];
    }
}
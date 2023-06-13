<?php
/**
 * User: dai.ho
 * Date: 10/16/2019
 * Time: 11:15 AM
 */

namespace App\V1\Validators\Payment;


use App\Http\Validators\ValidatorBase;

class PaymentOnePayRequestValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'orderId'     => 'required',
            'amount'      => 'required|numeric',
            'title'       => 'required',
            'type'        => 'required|in:PAYMENT,WITHDRAW,RECHARGE',
            'result_link' => 'required'
        ];
    }

    protected function attributes()
    {
        return [];
    }
}

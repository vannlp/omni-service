<?php
/**
 * User: dai.ho
 * Date: 10/16/2019
 * Time: 11:15 AM
 */

namespace App\V1\Validators\Payment;


use App\Http\Validators\ValidatorBase;

class PaymentMoMoRequestValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
//            'cart_id'   => 'required',
//            'amount'    => 'required|numeric',
//            'requestId' => 'required',
//            'title'     => 'required',
        ];
    }

    protected function attributes()
    {
        return [];
    }
}

<?php
/**
 * User: kpistech2
 * Date: 2020-11-02
 * Time: 15:14
 */

namespace App\V1\Validators\Payment;


use App\Http\Validators\ValidatorBase;

class PaymentVpBankRequestValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'url'     => 'required',
            // 'amount'      => 'required|numeric',
            'session_id'        => 'required',
        ];
    }

    protected function attributes()
    {
        return [];
    }
}
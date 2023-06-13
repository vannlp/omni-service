<?php

/**
 * User: dai.ho
 * Date: 5/02/2021
 * Time: 9:08 AM
 */

namespace App\Sync\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PaymentVirtualAccountValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            
            'transactionId'     =>  'required',
          
        ];
    }

    protected function attributes()
    {
        return [
            'transactionId'      => Message::get("transactionId"),
          
        ];
    }
}

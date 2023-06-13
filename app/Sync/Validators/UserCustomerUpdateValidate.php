<?php


namespace App\Sync\Validators;


use App\DMSSyncCustomer;
use App\Supports\Message;
use App\TM;
use App\Http\Validators\ValidatorBase;

class UserCustomerUpdateValidate extends ValidatorBase
{
    protected function rules()
    {
        return [
            'CUSTOMER_CODE' => 'required|exists:user_customers,code,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'CUSTOMER_CODE' => Message::get("CUSTOMER_CODE"),
            'PHONE'         => Message::get("PHONE"),
            'MOBIPHONE'     => Message::get("MOBIPHONE"),
            'CUSTOMER_ID'   => Message::get("CUSTOMER_ID"),
            'EMAIL'         => Message::get("EMAIL"),
        ];
    }
}
<?php


namespace App\Sync\Validators;


use App\DMSSyncCustomer;
use App\Supports\Message;
use App\TM;
use App\Http\Validators\ValidatorBase;

class UserCustomerCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'CUSTOMER_ID'   => [
                'required', 'numeric',
                function ($attribute, $value, $fail) {
                    $item = DMSSyncCustomer::where('customer_id', $value)
                        ->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                },
            ],
            'SHOP_ID'       => 'required|numeric',
            'CUSTOMER_CODE' => [
                'required','max:50',
                function ($attribute, $value, $fail) {
                    $item  = DMSSyncCustomer::where('code', $value)
                        ->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                },
            ],
            'SHORT_CODE'    => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $item  = DMSSyncCustomer::where('short_code', $value)
                        ->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                },
            ],
            'CUSTOMER_NAME' => 'required',
            'EMAIL'         => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $item = DMSSyncCustomer::where('email', $value)
                        ->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                },
            ],
            'PHONE'         => [
                'nullable',
                'max:12',
                function ($attribute, $value, $fail) {
                    if ($value != 'VT_NOT_PHONE') {
                        $item = DMSSyncCustomer::where('phone', $value)
                            ->whereNull('deleted_at')->get()->toArray();
                        if (!empty($item) && count($item) > 0) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                },
            ],
            'MOBIPHONE'     => [
                'nullable',
                'max:12',
                function ($attribute, $value, $fail) {
                    if ($value != 'VT_NOT_PHONE') {
                        $item = DMSSyncCustomer::where('mobiphone', $value)
                            ->whereNull('deleted_at')->get()->toArray();
                        if (!empty($item) && count($item) > 0) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                },
            ],
        ];
    }

    protected function attributes()
    {

        return [
            'CUSTOMER_ID'   => Message::get("CUSTOMER_ID"),
            'SHOP_ID'       => Message::get("SHOP_ID"),
            'CUSTOMER_CODE' => Message::get("CUSTOMER_CODE"),
            'SHORT_CODE'    => Message::get("SHORT_CODE"),
            'CUSTOMER_NAME' => Message::get("CUSTOMER_NAME"),
            'PHONE'         => Message::get("PHONE"),
            'MOBIPHONE'     => Message::get("MOBIPHONE"),
        ];
    }
}
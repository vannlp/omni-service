<?php

namespace App\Sync\Validators;


class OrderUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'status'      => 'required|in:' . implode(",", array_keys(STATUS_NAME_VIETTEL)),
            'orderNumber' => 'required|exists:orders,code,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'status'      => 'Status',
            'orderNumber' => 'OrderNumber',
        ];
    }
}
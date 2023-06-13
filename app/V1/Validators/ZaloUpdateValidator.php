<?php

namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ZaloUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'store_id'  => 'exists:stores,id,deleted_at,NULL',
            'zalo_oaid' => 'max:14'
        ];
    }

    protected function attributes()
    {
        return [
            'store_id'  => Message::get("store_id"),
            'zalo_oaid' => Message::get("zalo_oaid")
        ];
    }
}

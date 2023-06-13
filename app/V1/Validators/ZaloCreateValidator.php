<?php

namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ZaloCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'store_id'          => 'required|exists:stores,id,deleted_at,NULL',
            'zalo_access_token' => 'required',
            'zalo_oaid'         => 'required|max:30'
        ];
    }

    protected function attributes()
    {
        return [
            'store_id'          => Message::get("store_id"),
            'zalo_access_token' => Message::get("zalo_access_token"),
            'zalo_oaid'         => Message::get("zalo_oaid")
        ];
    }
}

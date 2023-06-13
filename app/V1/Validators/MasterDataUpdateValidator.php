<?php
/**
 * Date: 2/22/2019
 * Time: 11:56 AM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\MasterData;
use App\Supports\Message;
use Illuminate\Http\Request;

class MasterDataUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'       => 'required|exists:master_data,id,deleted_at,NULL',
            'type'     => 'nullable|exists:master_data_type,type,deleted_at,NULL',
            'store_id' => 'nullable|exists:stores,id,deleted_at,NULL',
            'code'     => 'required|unique_update:master_data',
        ];
    }

    protected function attributes()
    {
        return [
            'code'     => Message::get("code"),
            'type'     => Message::get("type"),
            'store_id' => Message::get("store_id"),
        ];
    }
}
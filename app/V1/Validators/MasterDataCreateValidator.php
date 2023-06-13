<?php
/**
 * Date: 2/21/2019
 * Time: 2:18 PM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\MasterData;
use App\Supports\Message;

class MasterDataCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'     => [
                'required',
                'max:50',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $masterData = MasterData::model()->where('code', $value)->first();
                        if (!empty($masterData)) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                    return true;
                },
            ],
            'name'     => 'required|max:200',
            'type'     => 'required|exists:master_data_type,type,deleted_at,NULL',
            'store_id' => 'required|exists:stores,id,deleted_at,NULL'
        ];
    }

    protected function attributes()
    {
        return [
            'code'     => Message::get("code"),
            'name'     => Message::get("name"),
            'store_id' => Message::get("store_id")
        ];
    }
}
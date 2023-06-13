<?php
/**
 * Date: 2/23/2019
 * Time: 5:34 PM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\MasterDataType;
use App\Supports\Message;

class MasterDataTypeCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'type' => [
                'required',
                'max:20',

                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $masterData = MasterDataType::model()->where('type', $value)->first();
                        if (!empty($masterData)) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }

                    }
                    return true;
                }
            ],
            'name' => 'required|max:50',
        ];
    }

    protected function attributes()
    {
        return [
            'type' => Message::get("type"),
            'name' => Message::get("name"),
        ];
    }
}
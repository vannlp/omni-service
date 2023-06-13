<?php
/**
 * Date: 2/23/2019
 * Time: 5:35 PM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\MasterDataType;
use App\Supports\Message;
use Illuminate\Http\Request;

class MasterDataTypeUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'   => 'required|exists:master_data_type,id,deleted_at,NULL',
            'type' => [
                'nullable',
                'max:15',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    $item = MasterDataType::where('type', $value)->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        if (count($item) > 1) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                },
            ],
            'name' => 'nullable|max:100',
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
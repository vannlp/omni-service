<?php
/**
 * Date: 1/16/2019
 * Time: 8:57 AM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\PermissionGroup;
use App\Supports\Message;
use Illuminate\Http\Request;

class PermissionGroupUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'   => 'exists:permission_groups,id,deleted_at,NULL',
            'code'     => [
                'required',
                'max:100',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    $item = PermissionGroup::where('code', $value)->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        if (count($item) > 1 || ($input['id'] > 0 && $item[0]['id'] != $input['id'])) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                }
            ],
            'name' => 'nullable|max:50',
        ];
    }

    protected function attributes()
    {
        return [
            'code' => Message::get("code"),
            'name' => Message::get("name"),
        ];
    }
}
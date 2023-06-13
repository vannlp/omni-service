<?php
/**
 * User: SangNguyen
 * Date: 3/27/2019
 * Time: 9:17 PM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Permission;
use App\Supports\Message;

class PermissionUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'     => [
                'nullable',
                'max:100',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $permission = Permission::model()->where('code', $value)->get();
                        if (count($permission) > 1) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                    return true;
                }
            ],
            'group_id' => 'required|exists:permission_groups,id,deleted_at,NULL',
            'name'     => 'nullable|max:100',
        ];
    }

    protected function attributes()
    {
        return [
            'code'     => Message::get("code"),
            'group_id' => Message::get("group_id"),
            'name'     => Message::get("name"),
        ];
    }
}
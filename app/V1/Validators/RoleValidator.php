<?php
/**
 * User: Administrator
 * Date: 12/10/2018
 * Time: 07:36 PM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Role;
use App\Supports\Message;
use Illuminate\Http\Request;

/**
 * Class RoleValidator
 * @package App\V1\Validators
 */
class RoleValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'         => 'integer|exists:roles,id,deleted_at,NULL',
            'code'       => [
                'nullable',
                'max:20',
                function ($attribute, $value, $fail) {
                    $item = Role::model()->where('code', $value)->get()->toArray();
                    if (count($item) > 1) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                }
            ],
            'name'       => 'required',
            'role_level' => 'required|numeric',
            'role_group' => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'code'       => Message::get("code"),
            'name'       => Message::get("name"),
            'role_level' => Message::get("role_level"),
            'role_group' => Message::get("role_group")
        ];
    }
}
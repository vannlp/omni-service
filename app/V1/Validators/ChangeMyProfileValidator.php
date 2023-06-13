<?php
/**
 * User: kpistech2
 * Date: 2019-10-23
 * Time: 20:00
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Profile;
use App\Supports\Message;
use App\User;
use Illuminate\Http\Request;

class ChangeMyProfileValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'user_id'  => 'required|exists:users,id,deleted_at,NULL',
            'name'     => 'required|max:100',
            'email'    => [
                'nullable',
                'email',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    $item = User::model()->where('email', $value)->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        if (count($item) > 1 || ($input['id'] > 0 && $item[0]['id'] != $input['id'])) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                },
            ],
            'phone'    => [
                'nullable',
                'max:20',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    $item = User::model()->where('phone', $value)->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        if (count($item) > 1 || ($input['id'] > 0 && $item[0]['id'] != $input['id'])) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                },
            ],
            'address'  => 'nullable|max:500',
            'password' => 'nullable|min:8',
        ];
    }

    protected function attributes()
    {
        return [
            'user_id'  => Message::get("users"),
            'phone'    => Message::get("phone"),
            'email'    => Message::get("email"),
            'name'     => Message::get("alternative_name"),
            'password' => Message::get("password"),
            'address'  => Message::get("address"),
        ];
    }
}

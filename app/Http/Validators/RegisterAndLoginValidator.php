<?php
/**
 * User: kpistech2
 * Date: 2019-10-25
 * Time: 22:36
 */

namespace App\Http\Validators;


class RegisterAndLoginValidator extends ValidatorBase
{
    protected function attributes()
    {
        return [
        ];
    }

    protected function rules()
    {
        return [
            'store_token' => 'required|exists:stores,token,deleted_at,NULL',
            'phone'       => 'nullable|numeric|not_in:0',
            'email'       => 'nullable|email|unique_create_company_delete:users,email',
            'name'        => 'required|min:5|max:40',
            'type'        => 'required|in:USER,CUSTOMER,PARTNER',
            'social_type' => 'required|in:GOOGLE,FACEBOOK,ZALO',
            'password'    => 'nullable|min:8',
        ];
    }
}
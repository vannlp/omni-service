<?php
/**
 * User: kpistech2
 * Date: 2019-10-25
 * Time: 21:40
 */

namespace App\Http\Validators;


class FacebookMappingValidator extends ValidatorBase
{
    protected function attributes()
    {
        return [
        ];
    }

    protected function rules()
    {
        return [
            'social_type' => 'required|in:FACEBOOK,GOOGLE',
            'id'          => 'required|unique:users,fb_id',
            'phone'       => 'required',
            'password'    => 'required',
        ];
    }


}
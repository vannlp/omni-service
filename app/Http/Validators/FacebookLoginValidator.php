<?php
/**
 * User: dai.ho
 * Date: 10/25/2019
 * Time: 01:22 PM
 */

namespace App\Http\Validators;


class FacebookLoginValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'social_type' => 'required|in:FACEBOOK,GOOGLE',
            'id'          => 'required',
            'device_id'   => 'required',
            'device_type' => 'required|in:DESKTOP,TABLE,PHONE,ANDROID,IOS,MOBILE',
        ];
    }

    protected function attributes()
    {
        return [
        ];
    }
}
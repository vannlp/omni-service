<?php
/**
 * User: dai.ho
 * Date: 10/25/2019
 * Time: 01:22 PM
 */

namespace App\Http\Validators;


class GoogleLoginValidator
{
    protected function rules()
    {
        return [
            'id'   => 'required',
            'name' => 'required',
        ];
    }

    protected function attributes()
    {
        return [
        ];
    }
}
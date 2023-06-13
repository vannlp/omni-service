<?php
/**
 * User: kpistech2
 * Date: 2020-06-08
 * Time: 22:59
 */

namespace App\V1\Validators\Feature;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class FeatureUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'   => 'required|exists:features,id,deleted_at,NULL',
            'code' => 'required|unique_update:features',
            'name' => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'id'       => Message::get("ID"),
            'code'     => Message::get("code"),
            'name'     => Message::get("alternative_name"),
            'email'    => Message::get("email"),
            'address'  => Message::get("address"),
            'tax_code' => Message::get("tax_code"),
            'phone'    => Message::get("phone"),
        ];
    }
}
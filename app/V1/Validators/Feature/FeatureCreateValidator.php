<?php
/**
 * User: kpistech2
 * Date: 2020-06-08
 * Time: 22:58
 */

namespace App\V1\Validators\Feature;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class FeatureCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code' => 'required|unique_create:features',
            'name' => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'code'     => Message::get("code"),
            'name'     => Message::get("alternative_name"),
        ];
    }
}
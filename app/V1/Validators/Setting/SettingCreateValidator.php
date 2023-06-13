<?php
/**
 * User: kpistech2
 * Date: 2020-07-04
 * Time: 00:45
 */

namespace App\V1\Validators\Setting;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class SettingCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code' => 'required|unique_create_company_delete:settings,code',
            'name' => 'required|unique_create_company_delete:settings,name',
            'data' => 'nullable|array'
        ];
    }

    protected function attributes()
    {
        return [
            'code' => Message::get("code"),
            'name' => Message::get("alternative_name"),
            'data' => Message::get("data"),
        ];
    }
}
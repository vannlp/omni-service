<?php
/**
 * User: kpistech2
 * Date: 2020-07-04
 * Time: 00:46
 */

namespace App\V1\Validators\Setting;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class SettingUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code' => 'required|unique_update_company_delete:settings,code',
            'name' => 'required|unique_update_company_delete:settings,name',
        ];
    }

    protected function attributes()
    {
        return [
            'code' => Message::get("code"),
            'name' => Message::get("alternative_name"),
        ];
    }
}
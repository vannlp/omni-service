<?php
/**
 * User: dai.ho
 * Date: 14/05/2020
 * Time: 4:35 PM
 */

namespace App\V1\Validators\UserGroup;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use App\TM;
use App\UserGroup;

class UserGroupCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code' => [
                'required',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $companyId = TM::getCurrentCompanyId();
                        $customerGroup = UserGroup::model()->where('code', $value)->where('company_id', $companyId)->first();
                        if (!empty($customerGroup)) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                    return true;
                }
            ],
            'name' => 'required|max:50',
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
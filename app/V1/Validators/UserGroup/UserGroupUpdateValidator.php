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
use Illuminate\Http\Request;

class UserGroupUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'                  => [
                'nullable',
                'max:20'
//                function ($attribute, $value, $fail,$_input = null) {
//                    if (!empty($value)) {
//                        $companyId = TM::getCurrentCompanyId();
//                        $customerGroup = UserGroup::model()->where('code', $value)->where('company_id', $companyId)->get()->toArray();
//                        $input = Request::capture();
//                        print_r($_input);die();
//                        print_r($customerGroup[0]['id']);die();
//                        if (count($customerGroup) >= 1) {
//                            return $fail(Message::get("unique", "$attribute: #$value"));
//                        }
//                    }
//                    return true;
//                }
            ],
            'name'                  => 'nullable|max:50',
        ];
    }

    protected function attributes()
    {
        return [
            'code'                  => Message::get("code"),
            'name'                  => Message::get("alternative_name"),
        ];
    }
}
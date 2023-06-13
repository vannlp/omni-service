<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\MembershipRank;
use App\Supports\Message;
use App\TM;

class MembershipRankCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'       => [
                'required',
                'max:20',
                function ($attribute, $value, $fail) {
                    $companyId = TM::getCurrentCompanyId();
                    $check     = MembershipRank::model()->where('code', $value)->where('company_id', $companyId)->first();
                    if (!empty($check)) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                    return true;
                }
            ],
            'name'       => 'required|max:50',
            'date_start' => 'required|date_format:Y-m-d',
            'date_end'   => 'required|date_format:Y-m-d',
            'point'      => [
                'required',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (!is_numeric($value) || $value < 0) {
                        return $fail(Message::get("V003", "$attribute: #$value"));
                    }
                    return true;
                }
            ],
        ];
    }

    protected function attributes()
    {
        return [
            'code'       => Message::get("code"),
            'name'       => Message::get("alternative_name"),
            'point'      => Message::get("point"),
            'company_id' => Message::get("companies"),
        ];
    }
}
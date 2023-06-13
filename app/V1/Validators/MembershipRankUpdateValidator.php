<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class MembershipRankUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'       => [
                'nullable',
                'max:20'
            ],
            'name'       => 'nullable|max:50',
            'date_start' => 'required|date_format:Y-m-d',
            'date_end'   => 'required|date_format:Y-m-d',
            'point'      => [
                'nullable',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        if (!is_numeric($value) || $value < 0) {
                            return $fail(Message::get("V003", "$attribute: #$value"));
                        }
                    }
                    return true;
                }
            ]
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
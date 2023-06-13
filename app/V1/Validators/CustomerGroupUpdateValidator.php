<?php


namespace App\V1\Validators;


use App\CustomerGroup;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class CustomerGroupUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'                  => [
                'nullable',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $customerGroup = CustomerGroup::model()->where('code', $value)->get();
                        if (count($customerGroup) > 1) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                    return true;
                }
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
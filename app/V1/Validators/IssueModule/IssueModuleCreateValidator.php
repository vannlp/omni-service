<?php


namespace App\V1\Validators\IssueModule;


use App\Http\Validators\ValidatorBase;
use App\IssueModule;
use App\Supports\Message;
use App\TM;

class IssueModuleCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code' => [
                'required',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $task = IssueModule::Model()->where([
                            'code' => $value,
                            'company_id' => TM::getCurrentCompanyId()
                            ])->first();
                        if (!empty($task)) {
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
            'name' => Message::get("name"),
        ];
    }
}
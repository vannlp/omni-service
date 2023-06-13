<?php


namespace App\V1\Validators\IssueModuleCategory;


use App\Http\Validators\ValidatorBase;
use App\IssueModuleCategory;
use App\Supports\Message;
use App\TM;
use Illuminate\Http\Request;

class IssueModuleCategoryCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'module_id' => 'required|exists:issue_modules,id,deleted_at,NULL',
            'code'      => [
                'required',
                'max:20',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    if (!empty($value)) {
                        $task = IssueModuleCategory::Model()
                            ->where('code', $value)
                            ->where('module_id', $input['module_id'])
                            ->where('company_id', TM::getCurrentCompanyId())
                            ->first();
                        if (!empty($task)) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                    return true;
                }
            ],
            'name'      => 'required|max:50',
        ];
    }

    protected function attributes()
    {
        return [
            'code'      => Message::get("code"),
            'name'      => Message::get("name"),
            'module_id' => Message::get("module_id"),
        ];
    }
}
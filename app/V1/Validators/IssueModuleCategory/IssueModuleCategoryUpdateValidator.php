<?php


namespace App\V1\Validators\IssueModuleCategory;


use App\Http\Validators\ValidatorBase;
use App\IssueModuleCategory;
use App\Supports\Message;
use App\TM;
use Illuminate\Http\Request;

class IssueModuleCategoryUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'        => 'required|exists:issue_module_categories,id,deleted_at,NULL',
            'code'      => [
                'required',
                'max:10',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    $item = IssueModuleCategory::where([
                        'code'=> $value,
                        'company_id' => TM::getCurrentCompanyId()
                    ])->whereNull('deleted_at')->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        if (count($item) > 1 || ($input['id'] > 0 && $item[0]['id'] != $input['id'])) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                }
            ],
            'name'      => 'nullable|max:50',
            'module_id' => 'nullable|exists:issue_modules,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'id'        => Message::get("id"),
            'code'      => Message::get("code"),
            'name'      => Message::get("name"),
            'module_id' => Message::get("module_id"),
        ];
    }
}
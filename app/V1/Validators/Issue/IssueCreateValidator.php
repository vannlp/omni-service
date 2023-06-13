<?php


namespace App\V1\Validators\Issue;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class IssueCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'module_category_id' => 'required|exists:issue_module_categories,id,deleted_at,NULL',
            'user_id'            => 'required|exists:users,id,deleted_at,NULL',
            'file_id'            => 'nullable|exists:files,id,deleted_at,NULL',
            'name'               => 'required|max:100',
            'deadline'           => 'nullable|date_format:d-m-Y H:i',
            'start_time'         => 'nullable|date_format:d-m-Y H:i',
            'priority'           => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'name'               => Message::get("name"),
            'module_category_id' => Message::get("module_category_id"),
            'deadline'           => Message::get("deadline"),
            'start_time'         => Message::get("deadline"),
            'progress'           => Message::get("progress"),
            'user_id'            => Message::get("user_id"),
            'priority'            => Message::get("priority"),
        ];
    }
}
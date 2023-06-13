<?php


namespace App\V1\Validators\Issue;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class IssueUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                 => 'required|exists:issues,id,deleted_at,NULL',
            'progress'           => 'nullable|integer',
            'module_category_id' => 'nullable|exists:issue_module_categories,id,deleted_at,NULL',
            'user_id'            => 'nullable|exists:users,id,deleted_at,NULL',
            'file_id'            => 'nullable|exists:files,id,deleted_at,NULL',
            'name'               => 'nullable|max:100',
            'deadline'           => 'date_format:d-m-Y H:i',
            'start_time'         => 'date_format:d-m-Y H:i',
        ];
    }

    protected function attributes()
    {
        return [
            'id'                 => Message::get("id"),
            'name'               => Message::get("name"),
            'deadline'           => Message::get("deadline"),
            'start_time'         => Message::get("start_time"),
            'module_category_id' => Message::get("module_category_id"),
            'user_id'            => Message::get("user_id"),
            'progress'           => Message::get("progress"),
        ];
    }
}
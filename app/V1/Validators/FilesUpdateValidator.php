<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class FilesUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'        => 'required|exists:files,id,deleted_at,NULL',
            'folder_id' => 'nullable|exists:folders,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'folder_id' => Message::get("folder_id"),
        ];
    }
}
<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class FilesCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'folder_id' => 'nullable|exists:folders,id,deleted_at,NULL',
            'file'      => 'required',
//            'store_id'  => 'required|exists:stores,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'folder_id' => Message::get("folder_id"),
            'file'      => Message::get("files"),
//            'store_id'  => Message::get("stores"),
        ];
    }
}
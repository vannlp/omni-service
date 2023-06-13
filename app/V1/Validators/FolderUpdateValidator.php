<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class FolderUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'       => 'nullable|exists:folders,id,deleted_at,NULL',
            'store_id' => 'required|exists:stores,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'id'       => Message::get("id"),
            'store_id' => Message::get("stores"),
        ];
    }
}
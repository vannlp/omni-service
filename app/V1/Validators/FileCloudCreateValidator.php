<?php


namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class FileCloudCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'category' => 'exists:file_categories,id,deleted_at,NULL',
            'shop'     => 'required|exists:stores,id,deleted_at,NULL',
            'title'    => 'required|max:100',
            'file'     => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'category' => Message::get("category"),
            'shop'     => Message::get("store_id"),
            'title'    => Message::get("title"),
            'file'     => Message::get("file", 'file')
        ];
    }
}

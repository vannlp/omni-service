<?php


namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class FileCloudUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'category' => 'exists:file_categories,id,deleted_at,NULL',
            'shop'     => 'exists:stores,id,deleted_at,NULL',
            'title'    => 'max:100',
            'id'       => 'exists:file_clouds,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'category' => Message::get("category"),
            'shop'     => Message::get("store_id"),
            'title'    => Message::get("title"),
            'id'       => Message::get("id")
        ];
    }
}

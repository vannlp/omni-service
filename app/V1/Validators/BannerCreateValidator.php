<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class BannerCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'title'    => 'required',
            'code'     => 'required',
            'image'    => 'exists:files,id,deleted_at,NULL',
            'store_id' => 'required|exists:stores,id,deleted_at,NULL',
            'details'  => 'array',
        ];
    }

    protected function attributes()
    {
        return [
            'title'   => Message::get("title"),
            'image'   => Message::get("image"),
            'code'    => Message::get("code"),
            'details' => Message::get("details"),
            'store_id' => Message::get("stores"),
        ];
    }
}
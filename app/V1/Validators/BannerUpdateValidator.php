<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class BannerUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'       => 'required|exists:banners,id,deleted_at,NULL',
            'code'     => 'required',
            'title'    => 'nullable',
            'image'    => 'nullable|exists:files,id,deleted_at,NULL',
            'details'  => 'array',
        ];
    }

    protected function attributes()
    {
        return [
            'code'     => Message::get("code"),
            'title'    => Message::get("title"),
            'image'    => Message::get("image"),
            'details'  => Message::get("details"),
        ];
    }
}
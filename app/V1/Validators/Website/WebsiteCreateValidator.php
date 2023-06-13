<?php


namespace App\V1\Validators\Website;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class WebsiteCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'name'     => 'required|unique_create_delete:websites',
            'domain'   => 'required|unique_create_delete:websites',
            'store_id' => 'required|exists:stores,id,deleted_at,NULL',
            'blog_id'  => 'nullable|exists:blogs,id,deleted_at,NULL',
            'status'   => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'code'     => Message::get("code"),
            'domain'   => Message::get("domain"),
            'store_id' => Message::get("stores"),
            'blog_id'  => Message::get("blog_id"),
            'status'   => Message::get("status"),
        ];
    }
}
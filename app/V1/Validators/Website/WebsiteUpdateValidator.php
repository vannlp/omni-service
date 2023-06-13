<?php


namespace App\V1\Validators\Website;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Http\Request;

class WebsiteUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'       => 'required|exists:websites,id,deleted_at,NULL',
            'name'     => 'required|unique_update_delete:websites',
            'domain'   => 'required|unique_update_delete:websites',
            'blog_id'  => 'nullable|exists:blogs,id,deleted_at,NULL',
            'store_id' => 'required|exists:stores,id,deleted_at,NULL',
            'status'   => 'required'
        ];
    }

    protected function attributes()
    {
        return [
            'code'     => Message::get("code"),
            'domain'   => Message::get("domain"),
            'store_id' => Message::get("stores"),
            'blog_id'  => Message::get("blogs"),
            'status'   => Message::get("status"),
        ];
    }
}
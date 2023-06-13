<?php


namespace App\V1\Validators\BlogPost;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class BlogCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'name'       => 'required',
            'website_id' => 'required|exists:websites,id,deleted_at,NULL'
        ];
    }

    protected function attributes()
    {
        return [
            'name'       => Message::get("alternative_name"),
            'website_id' => Message::get("website_id"),
        ];
    }
}
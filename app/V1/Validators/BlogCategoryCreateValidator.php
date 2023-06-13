<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class BlogCategoryCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'name'       => 'required',
            'blog_id'    => 'required|exists:blogs,id,deleted_at,NULL',
            'website_id' => 'required|exists:websites,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'name'       => Message::get("name"),
            'blog_id'    => Message::get("blog_id"),
            'website_id' => Message::get("website_id"),
        ];
    }
}
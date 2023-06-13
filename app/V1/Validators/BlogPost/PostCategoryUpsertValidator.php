<?php


namespace App\V1\Validators\BlogPost;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PostCategoryUpsertValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'    => 'nullable|exists:post_categories,id,deleted_at,NULL',
            'title' => 'required',
            'code'  => 'unique_update_company_delete:post_categories,code',
        ];
    }

    protected function attributes()
    {
        return [
            'id'    => Message::get("id"),
            'title' => Message::get("title"),
            'code'  => Message::get("code")
        ];
    }
}
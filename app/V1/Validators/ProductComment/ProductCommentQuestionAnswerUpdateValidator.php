<?php


namespace App\V1\Validators\ProductComment;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ProductCommentQuestionAnswerUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'         => 'required|exists:product_comments,id,deleted_at,NULL',
            'product_id' => 'required|exists:products,id,deleted_at,NULL',
            'content'    => 'required|min:30',
            'parent_id'  => 'nullable|exists:product_comments,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'product_id' => Message::get("products"),
            'content'    => Message::get("content"),
            'parent_id'  => Message::get("parent"),
        ];
    }
}
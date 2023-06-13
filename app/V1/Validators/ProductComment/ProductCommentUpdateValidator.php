<?php


namespace App\V1\Validators\ProductComment;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ProductCommentUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'         => 'required|exists:product_comments,id,deleted_at,NULL',
            'product_id' => 'nullable|exists:products,id,deleted_at,NULL',
            'content'    => 'nullable|min:5',
            'type'       => 'nullable|in:' . PRODUCT_COMMENT_TYPE_RATE . "," . PRODUCT_COMMENT_TYPE_QAA . "," . PRODUCT_COMMENT_TYPE_REPLY . "," . PRODUCT_COMMENT_TYPE_COMMENT . "," . PRODUCT_COMMENT_TYPE_REPLY_COMMENT,
            'rate'       => 'nullable|numeric',
            'parent_id'  => 'nullable|exists:product_comments,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'id'         => Message::get("ID"),
            'product_id' => Message::get("products"),
            'content'    => Message::get("content"),
            'rate'       => Message::get("rate"),
            'type'       => Message::get("type"),
        ];
    }
}
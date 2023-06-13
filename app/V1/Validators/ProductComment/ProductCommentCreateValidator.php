<?php


namespace App\V1\Validators\ProductComment;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ProductCommentCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'product_id' => 'nullable|exists:products,id,deleted_at,NULL',
            'type'       => 'required|in:' . PRODUCT_COMMENT_TYPE_RATE . "," . PRODUCT_COMMENT_TYPE_QAA . "," . PRODUCT_COMMENT_TYPE_REPLY . "," . PRODUCT_COMMENT_TYPE_COMMENT. "," . PRODUCT_COMMENT_TYPE_REPLY_COMMENT. "," . PRODUCT_COMMENT_TYPE_QUESTION.",".PRODUCT_COMMENT_TYPE_RATESHIPPING,
            'rate'       => 'nullable|numeric',
            'parent_id'  => 'nullable|exists:product_comments,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'product_id' => Message::get("products"),
            'rate'       => Message::get("rate"),
            'type'       => Message::get("type"),
            'parent_id'  => Message::get("parent"),
        ];
    }
}
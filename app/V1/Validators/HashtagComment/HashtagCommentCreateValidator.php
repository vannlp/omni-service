<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:19
 */

namespace App\V1\Validators\HashtagComment;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class HashtagCommentCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'          => 'required|unique:hashtag_comments,code',
            'content'       => 'required',
            'is_choose'     => 'required',
            // 'store_id' => 'required|exists:stores,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'code'        => Message::get("code"),
            'content'     => Message::get("value"),
            'is_choose'   => Message::get("value"),
            // 'store_id' => Message::get("stores"),
        ];
    }
}
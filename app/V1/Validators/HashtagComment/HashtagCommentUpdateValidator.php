<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:19
 */

namespace App\V1\Validators\HashtagComment;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class HashtagCommentUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'            => 'required|exists:units,id,deleted_at,NULL',
            'code'          => 'required',
            'content'       => 'required',
            'is_choose'     => 'required',
            // 'store_id' => 'required|exists:stores,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'id'          => Message::get("code"),
            'code'        => Message::get("code"),
            'content'     => Message::get("value"),
            'is_choose'   => Message::get("value"),
            // 'store_id' => Message::get("stores"),
        ];
    }
}
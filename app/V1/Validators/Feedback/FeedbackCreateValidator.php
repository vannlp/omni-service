<?php

/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:59 PM
 */

namespace App\V1\Validators\Feedback;

use App\Http\Validators\ValidatorBase;
use App\Feedback;
use App\Supports\Message;
use Illuminate\Http\Request;

class FeedbackCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'title'      => 'required',
            'company_id' => 'exists:companies,id,deleted_at,NULL',
            'user_id'    => 'exists:users,id,deleted_at,NULL',
            'content'    => 'required',
            'image'      => 'nullable',
        ];
    }

    protected function attributes()
    {
        return [
            'title'      => Message::get("title"),
            'content'    => Message::get("content"),
            'image'      => Message::get("image"),
            'company_id' => Message::get("company_id"),
            'user_id'    => Message::get("user_id"),
        ];
    }
}

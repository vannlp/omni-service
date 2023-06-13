<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:19
 */

namespace App\V1\Validators\Feedback;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ReasonCancelCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'value'     => 'required',
            'type'      => 'required',
            // 'store_id' => 'required|exists:stores,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'value'     => Message::get("value"),
            'type'      => Message::get("value"),
            // 'store_id' => Message::get("stores"),
        ];
    }
}
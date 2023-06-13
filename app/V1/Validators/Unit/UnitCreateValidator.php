<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:19
 */

namespace App\V1\Validators\Unit;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class UnitCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'     => 'required',
            'name'     => 'required',
            'store_id' => 'required|exists:stores,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'code'     => Message::get("code"),
            'name'     => Message::get("alternative_name"),
            'store_id' => Message::get("stores"),
        ];
    }
}
<?php
/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:45 AM
 */

namespace App\V1\Validators\Area;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class AreaCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'     => 'required|unique_create_delete:areas',
            'name'     => 'required',
            'image_id' => 'required|exists:files,id,deleted_at,NULL',
            'store_id' => 'required|exists:stores,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'code'     => Message::get("code"),
            'image_id' => Message::get("image_id"),
            'name'     => Message::get("alternative_name"),
            'store_id' => Message::get("stores"),
        ];
    }
}
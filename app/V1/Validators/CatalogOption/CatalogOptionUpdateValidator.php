<?php
/**
 * User: dai.ho
 * Date: 1/06/2020
 * Time: 1:30 PM
 */

namespace App\V1\Validators\CatalogOption;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class CatalogOptionUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'               => 'required|exists:catalog_options,id,deleted_at,NULL',
            'code'             => 'required',
            'name'             => 'required',
            'order'            => 'nullable|numeric',
            'store_id'         => 'required|exists:stores,id,deleted_at,NULL',
            'values'           => 'nullable|array',
            'values.*.name'    => 'required',
            'values.*.order'   => 'nullable|numeric',
            'values.*.unit_id' => 'required|exists:units,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'code'             => Message::get("code"),
            'name'             => Message::get("alternative_name"),
            'order'            => Message::get("order"),
            'store_id'         => Message::get("stores"),
            'values'           => Message::get("values"),
            'values.*.name'    => Message::get("values"),
            'values.*.order'   => Message::get("order"),
            'values.*.unit_id' => Message::get("units"),
        ];
    }
}
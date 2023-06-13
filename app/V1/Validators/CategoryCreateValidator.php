<?php
/**
 * Created by PhpStorm.
 * User: kpistech2
 * Date: 2019-02-18
 * Time: 23:00
 */

namespace App\V1\Validators;


use App\Category;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;


class CategoryCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'                       => 'required',
            'name'                       => 'required|max:50',
            'description'                => 'max:500',
            'type'                       => 'required|in:PRODUCT,SERVICE',
            'image_id'                   => 'nullable|exists:files,id,deleted_at,NULL',
            'area_id'                    => 'required|exists:areas,id,deleted_at,NULL',
//            'property_ids'               => 'nullable|array',
            'store_details'              => 'required|array',
            'store_details.*.store_id'   => 'required|exists:stores,id,deleted_at,NULL',
            'store_details.*.store_code' => 'required',
            'store_details.*.store_name' => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'code'                       => Message::get("code"),
            'name'                       => Message::get("alternative_name"),
            'description'                => Message::get("description"),
            'type'                       => Message::get("type"),
            'image_id'                   => Message::get("img"),
            'area_id'                    => Message::get("area_id"),
            'property_ids'               => Message::get("properties"),
            'store_details'              => Message::get("stores"),
            'store_details.*.store_id'   => Message::get("stores"),
            'store_details.*.store_code' => Message::get("stores"),
            'store_details.*.store_name' => Message::get("stores"),
        ];
    }
}

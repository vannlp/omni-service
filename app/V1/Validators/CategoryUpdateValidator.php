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
use Illuminate\Http\Request;

class CategoryUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                         => 'required|exists:categories,id,deleted_at,NULL',
            'code'                       => 'required',
            'name'                       => 'required',
            'description'                => 'max:500',
            'image_id'                   => 'nullable|exists:files,id,deleted_at,NULL',
            'area_id'                    => 'required|exists:areas,id,deleted_at,NULL',
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
            'image_id'                   => Message::get("img"),
            'area_id'                    => Message::get("area_id"),
            'store_details'              => Message::get("stores"),
            'store_details.*.store_id'   => Message::get("stores"),
            'store_details.*.store_code' => Message::get("stores"),
            'store_details.*.store_name' => Message::get("stores"),
        ];
    }
}
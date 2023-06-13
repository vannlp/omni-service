<?php
/**
 * User: dai.ho
 * Date: 28/01/2021
 * Time: 3:12 PM
 */

namespace App\Sync\Validators;


use App\Supports\Message;

class ProductCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'ProductCode' => 'required|string|max:50',
            'ProductName' => 'max:100',
            'Status'      => 'required|integer|max:1',
            'Convfact'    => 'required',
            'Description' => 'nullable',
            'Category'    => 'exists:categories,code,deleted_at,NULL',
            'subCategory' => 'exists:categories,code,deleted_at,NULL',
            'UOM1'        => 'required|exists:units,code,deleted_at,NULL',
            'UOM2'        => 'required|exists:units,code,deleted_at,NULL',
            'NetWeight'   => 'numeric',
            'GrossWeight' => 'numeric',
            'Expire_date' => 'nullable',
        ];
    }

    protected function attributes()
    {
        return [
            'ProductName'    => Message::get("name"),
            'ProductCode'    => Message::get("code"),
            'category_codes' => Message::get("categories"),
            'Status'         => Message::get("status"),
            'Description'    => Message::get("description"),
            'Category'       => Message::get("categories"),
            'subCategory'    => Message::get("categories"),
            'UOM1'           => Message::get("units"),
            'UOM2'           => Message::get("units"),
            'NetWeight'      => Message::get("weight"),
            'GrossWeight'    => Message::get("weight"),
        ];
    }
}
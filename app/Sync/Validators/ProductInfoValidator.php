<?php
/**
 * User: dai.ho
 * Date: 28/01/2021
 * Time: 3:12 PM
 */

namespace App\Sync\Validators;
use App\Http\Validators\ValidatorBase;

use App\Supports\Message;

class ProductInfoValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'                  => 'required|string|max:50|unique:product_info_dms_imports',
            'product_info_name'     => 'required',
            'description'           => 'nullable',
            'status'                => 'required',
            'type'                  => 'required',
            
        ];
    }

    protected function attributes()
    {
        return [

            'product_info_name'    => Message::get("name"),
            'code'                 => Message::get("code"),
            'description'          => Message::get("description"),
            'status'               => Message::get("status"),
            'type'                 => Message::get("type"),
           
        ];
    }
}
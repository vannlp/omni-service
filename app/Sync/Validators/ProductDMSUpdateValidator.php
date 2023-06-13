<?php
/**
 * User: dai.ho
 * Date: 28/01/2021
 * Time: 3:12 PM
 */

namespace App\Sync\Validators;


use App\Supports\Message;
use App\Http\Validators\ValidatorBase;
class ProductDMSUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            
            "code"                           => 'required|string|max:50|unique:product_dms_imports',
            "cat_id"                         => 'required',
            "product_name"                   => 'required',
           
        
        ];
    }

    protected function attributes()
    {
        return [
            "code"                           => Message::get("code"),        
            "cat_id"                         => Message::get("cat_id"),     
            "product_name"                   => Message::get("product_name"),   
           
        ];
    }
}
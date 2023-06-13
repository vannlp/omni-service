<?php
/**
 * User: kpistech2
 * Date: 2020-05-09
 * Time: 22:00
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class CompanyUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'       => 'required|exists:companies,id,deleted_at,NULL',
            'code'     => 'required|unique_update:companies',
            'name'     => 'required',
            'email'    => 'required|email',
            'address'  => 'required',
//            'avatar_id'=> 'required',
//            'avatar'   => 'required',
            'tax_code' => 'required|unique_update:companies',
            'phone'    => 'required|max:14',
        ];
    }

    protected function attributes()
    {
        return [
            'id'       => Message::get("ID"),
            'code'     => Message::get("code"),
            'name'     => Message::get("alternative_name"),
            'email'    => Message::get("email"),
            'address'  => Message::get("address"),
            'tax_code' => Message::get("tax_code"),
            'phone'    => Message::get("phone"),
        ];
    }
}
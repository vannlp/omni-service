<?php

/**
 * User: kpistech2
 * Date: 2020-08-02
 * Time: 16:37
 */

namespace App\Http\Validators;


use App\Supports\Message;

class ForgetPasswordValidator extends ValidatorBase
{
     protected function rules()
     {
          return [
               'phone'        => 'required|exists:users,phone,deleted_at,NULL'  
          ];
     }

     protected function attributes()
     {
          return [
               'phone'        => Message::get("phone")
          ];
     }
}

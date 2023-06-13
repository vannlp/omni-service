<?php

/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:59 PM
 */

namespace App\V1\Validators\RotationResult;

use App\Http\Validators\ValidatorBase;
use App\RotationResult;
use App\Supports\Message;
use Illuminate\Http\Request;

class RotationResultCreateValidator extends ValidatorBase
{
     protected function rules()
     {
          return [
               'rotation_id'         => 'exists:rotations,id,deleted_at,NULL',
               'name'                => 'required',
               'description'         => 'required',
               'ratio'               => 'required',
          ];
     }

     protected function attributes()
     {
          return [
               'rotation_id'   => Message::get("title"),
               'name'          => Message::get("name"),
               'description'   => Message::get("description"),
               'ratio'         => Message::get("ratio")
          ];
     }
}

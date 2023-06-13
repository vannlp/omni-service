<?php

/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:34 PM
 */

namespace App\V1\Models;

use App\Supports\Message;
use App\RotationResult;
use App\TM;

class RotationResultModel extends AbstractModel
{
     public function __construct(RotationResult $model = null)
     {
          parent::__construct($model);
     }

     public function upsert($input)
     {
          $id        = !empty($input['id']) ? $input['id'] : 0; 
          if ($id) {
               $rotationResult = RotationResult::find($id);
               if (empty($rotationResult)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
               }
               $rotationResult->rotation_id  = array_get($input, 'rotation_id', $rotationResult->rotation_id);
               $rotationResult->name         = array_get($input, 'name', $rotationResult->name);
               $rotationResult->code         = array_get($input, 'code', $rotationResult->code);
               $rotationResult->type         = array_get($input, 'type', $rotationResult->type);
               $rotationResult->coupon_id    = array_get($input, 'coupon_id', $rotationResult->coupon_id);
               $rotationResult->coupon_name  = array_get($input, 'coupon_name', $rotationResult->coupon_name);
               $rotationResult->description  = array_get($input, 'description', $rotationResult->description);
               $rotationResult->ratio        = array_get($input, 'ratio', $rotationResult->ratio);
               $rotationResult->updated_at   = date("Y-m-d H:i:s", time());
               $rotationResult->updated_by   = TM::getCurrentUserId();
               $rotationResult->save();
          } else {
               $param        = [
                    'rotation_id'  => $input['rotation_id'],
                    'name'         => $input['name'],
                    'code'         => $input['code'],
                    'type'         => $input['type'],
                    'coupon_id'    => $input['coupon_id'],
                    'coupon_name'  => $input['coupon_name'],
                    'description'  => $input['description'],
                    'ratio'        => $input['ratio'],
               ];
               $rotationResult = $this->create($param);
          }
          return $rotationResult;
     }
}

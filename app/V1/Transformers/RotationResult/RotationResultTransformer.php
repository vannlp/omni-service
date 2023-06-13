<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\V1\Transformers\RotationResult;

use App\RotationResult;
use App\File;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class RotationResultTransformer extends TransformerAbstract
{
     public function transform(RotationResult $item)
     {
          try {
               
               return [
                    'id'             => $item->id,
                    'rotation_id'    => $item->rotation_id,
                    'rotation_name'  => array_get($item, 'rotation.name'),
                    'name'           => $item->name,
                    'code'           => $item->code,
                    'type'           => $item->type,
                    'coupon_id'      => $item->coupon_id,
                    'coupon_name'    => array_get($item, 'coupon.name'),
                    'coupon_code'    => array_get($item, 'coupon.code'),
                    'description'    => $item->description,
                    'ratio'          => $item->ratio,
                    'created_at'     => date('d-m-Y', strtotime($item->created_at)),
                    'created_by'     => object_get($item, 'createdBy.profile.full_name'),
                    'updated_at'     => date('d-m-Y', strtotime($item->updated_at)),
                    'updated_by'     => object_get($item, 'updatedBy.profile.full_name'),
               ]; 
          } catch (\Exception $ex) {
               $response = TM_Error::handle($ex);
               throw new \Exception($response['message'], $response['code']);
          }
     }
}

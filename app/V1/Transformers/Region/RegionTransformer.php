<?php

/**
 * User: dai.ho
 * Date: 9/06/2020
 * Time: 3:55 PM
 */

namespace App\V1\Transformers\Region;


use App\Region;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class RegionTransformer extends TransformerAbstract
{
     public function transform(Region $item)
     {
          try {
               $data                  = [];
//               $sectors               = $item->sector;
//               foreach ($sectors as $value) {
//                    $data[]   = [
//                         'id'        => $value['id'],
//                         'code'      => $value['code'],
//                         'name'      => $value['name'],
//                    ];
//               }

               return [
                    'id'          => $item->id,
                    'code'        => $item->code,
                    'name'        => $item->name,
                    'ward_code'        => $item->ward_code,
                    'ward_full_name'        => $item->ward_full_name,
                    'district_code'        => $item->district_code,
                    'district_full_name'        => $item->district_full_name,
                    'city_code'        => $item->city_code,
                    'city_full_name'        => $item->city_full_name,
                    'distributor_code'        => $item->distributor_code,
                    'distributor_name'        => $item->distributor_name,
//                    'sector'      => $data,
                    'created_at'  => date('d-m-Y', strtotime($item->created_at)),
                    'updated_at'  => date('d-m-Y', strtotime($item->updated_at)),
                    'created_by'  => object_get($item, 'createdBy.profile.full_name'),
                    'updated_by'  => object_get($item, 'createdBy.profile.full_name')
               ];
          } catch (\Exception $ex) {
               $response = TM_Error::handle($ex);
               throw new \Exception($response['message'], $response['code']);
          }
     }
}

<?php


namespace App\V1\Transformers\Distributor;


use App\Distributor;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class DistributorTransformer extends TransformerAbstract
{
     public function transform(Distributor $distributor)
     {
          try {
               return [
                    'id'                 => $distributor->id,
                    'name'               => $distributor->name,
                    'code'               => $distributor->code,
                    'city_code'          => $distributor->city_code,
                    'city_full_name'     => $distributor->city_full_name,
                    'district_code'      => $distributor->district_code,
                    'district_full_name' => $distributor->district_full_name,
                    'ward_code'          => $distributor->ward_code,
                    'ward_full_name'     => $distributor->ward_full_name,
                    'is_active'          => $distributor->is_active,
                    'created_at'         => date('d/m/Y H:i', strtotime($distributor->created_at)),
                    'updated_at'         => date('d/m/Y H:i', strtotime($distributor->updated_at)),
               ];
          } catch (\Exception $ex) {
               $response = TM_Error::handle($ex);
               throw new \Exception($response['message'], $response['code']);
          }
     }
}

<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\Sync\Transformers;

use App\Routing;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class RoutingSyncTransformers extends TransformerAbstract
{
    public function transform(Routing $routing)
    {
        try {
            return [
                'id'                =>  $routing->id,
                'routing_id'        =>  $routing->routing_id,
                'routing_code'      =>  $routing->routing_code,
                'routing_name'      =>  $routing->routing_name,
                'shop_id'           =>  $routing->shop_id,
                'status'            =>  $routing->status,
                // 'created_at'        =>  $input['created_at'] ?? null,
                // 'created_by'        =>  $input['created_by'] ?? null,
                // 'updated_at'        =>  $input['updated_at'] ?? null,
                // 'updated_by'        =>  $input['updated_by'] ?? null
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

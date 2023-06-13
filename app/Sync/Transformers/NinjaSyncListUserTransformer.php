<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\Sync\Transformers;

use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class NinjaSyncListUserTransformer extends TransformerAbstract
{
    public function transform(NinjaSyncListUserTransformer $ninjaSyncListUserTransformer)
    {
        try {
            return [
                'id'         => $ninjaSyncListUserTransformer->id,
                'code'       => $ninjaSyncListUserTransformer->code,
                'name'       => $ninjaSyncListUserTransformer->name,
                'company_id' => $ninjaSyncListUserTransformer->company_id,
                // 'created_at'    => date('d-m-Y', strtotime($ninjaSync->created_at)),
                // 'created_by'    => object_get($ninjaSync, 'createdBy.profile.full_name'),
                // 'updated_at'    => date('d-m-Y', strtotime($ninjaSync->updated_at)),
                // 'updated_by'    => object_get($ninjaSync, 'updatedBy.profile.full_name'),
            ];
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

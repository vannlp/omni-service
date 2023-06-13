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

class NinjaSyncFilterInteractiveTransformer extends TransformerAbstract
{
    public function transform(NinjaSyncFilterInteractiveTransformer $ninjaSyncFilterInteractiveTransformer)
    {
        try {
            return [
                'id'          => $ninjaSyncFilterInteractiveTransformer->id,
                'code'        => $ninjaSyncFilterInteractiveTransformer->code,
                'user_name'   => $ninjaSyncFilterInteractiveTransformer->user_name,
                'gender'      => $ninjaSyncFilterInteractiveTransformer->gender,
                'location'    => $ninjaSyncFilterInteractiveTransformer->location,
                'interactive' => $ninjaSyncFilterInteractiveTransformer->interactive,
                'company_id'  => $ninjaSyncFilterInteractiveTransformer->company_id,
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

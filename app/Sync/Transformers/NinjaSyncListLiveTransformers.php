<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\Sync\Transformers;

use App\NinjaSyncListLive;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class NinjaSyncListLiveTransformers extends TransformerAbstract
{
    public function transform(NinjaSyncListLive $ninjaSyncListLive)
    {
        try {
            return [
                'id'           => $ninjaSyncListLive->id,
                'code'         => $ninjaSyncListLive->code,
                'user_name'    => $ninjaSyncListLive->user_name,
                'phone'        => $ninjaSyncListLive->phone,
                'comment'      => $ninjaSyncListLive->comment,
                'company_id'   => $ninjaSyncListLive->company_id,
                'created_date' => date('d-m-Y', strtotime($ninjaSyncListLive->created_date)),
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

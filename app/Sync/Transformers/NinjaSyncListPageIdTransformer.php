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

class NinjaSyncListPageIdTransformer extends TransformerAbstract
{
    public function transform(NinjaSyncListPageIdTransformer $ninjaSyncListPageIdTransformer)
    {
        try {
            return [
                'id'          => $ninjaSyncListPageIdTransformer->id,
                'code'        => $ninjaSyncListPageIdTransformer->code,
                'name'   => $ninjaSyncListPageIdTransformer->name,
                'like'      => $ninjaSyncListPageIdTransformer->like,
                'follow'    => $ninjaSyncListPageIdTransformer->follow,
                'checkin' => $ninjaSyncListPageIdTransformer->checkin,
                'email' => $ninjaSyncListPageIdTransformer->email,
                'location' => $ninjaSyncListPageIdTransformer->location,
                'category' => $ninjaSyncListPageIdTransformer->category,
                'created_date' => date('d-m-Y', strtotime($ninjaSyncListPageIdTransformer->created_date)),
                'company_id'  => $ninjaSyncListPageIdTransformer->company_id,
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

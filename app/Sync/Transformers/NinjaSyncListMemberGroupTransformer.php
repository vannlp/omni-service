<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\Sync\Transformers;

use App\NinjaSyncListMemberGroup;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class NinjaSyncListMemberGroupTransformer extends TransformerAbstract
{
    public function transform(NinjaSyncListMemberGroup $ninjaSyncListMemberGroup)
    {
        try {
            return [
                'id'         => $ninjaSyncListMemberGroup->id,
                'code'       => $ninjaSyncListMemberGroup->code,
                'name'       => $ninjaSyncListMemberGroup->name,
                'status'     => $ninjaSyncListMemberGroup->gender,
                'location'   => $ninjaSyncListMemberGroup->location,
                'company_id' => $ninjaSyncListMemberGroup->company_id,
                'member'     => $ninjaSyncListMemberGroup->admin,
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

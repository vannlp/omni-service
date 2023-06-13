<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\Sync\Transformers;

use App\NinjaSyncUidFriend;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class NinjaSyncUidFriendTransformer extends TransformerAbstract
{
    public function transform(NinjaSyncUidFriend $ninjaSyncUidFriend)
    {
        try {
            return [
                'id'         => $ninjaSyncUidFriend->id,
                'code'       => $ninjaSyncUidFriend->code,
                'name'       => $ninjaSyncUidFriend->name,
                'gender'     => $ninjaSyncUidFriend->gender,
                'location'   => $ninjaSyncUidFriend->location,
                'company_id' => $ninjaSyncUidFriend->company_id,
                'birthday'   => date('d-m-Y', strtotime($ninjaSyncUidFriend->birthday)),
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

<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\V1\Models\AbstractModel;

class NinjaSyncUidFriendModel extends AbstractModel
{
    public function __construct(NinjaSyncUidFriendModel $ninjaSyncUidFriendModel = null)
    {
        parent::__construct($ninjaSyncUidFriendModel);
    }

    public function upsert($input)
    {
        $companyId               = !empty($input['company_id']) ? $input['company_id'] : null;
        $param                   = [
            'code'       => $input['code'],
            'name'       => $input['user_name'],
            'gender'     => !empty($input['gender']) ? $input['gender'] : null,
            'birthday'   => !empty($input['birthday']) ? $input['birthday'] : null,
            'location'   => !empty($input['location']) ? $input['location'] : null,
            'company_id' => $companyId,
        ];
        $ninjaSyncUidFriendModel = $this->create($param);

        return $ninjaSyncUidFriendModel;
    }
}


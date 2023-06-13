<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\V1\Models\AbstractModel;

class NinjaSyncListUserModel extends AbstractModel
{
    public function __construct(NinjaSyncListUserModel $ninjaSyncListUserModel = null)
    {
        parent::__construct($ninjaSyncListUserModel);
    }

    public function upsert($input)
    {
        $companyId              = !empty($input['company_id']) ? $input['company_id'] : null;
        $param                  = [
            'code'       => $input['code'],
            'name'       => $input['name'],
            'company_id' => $companyId,
        ];
        $ninjaSyncListUserModel = $this->create($param);

        return $ninjaSyncListUserModel;
    }
}



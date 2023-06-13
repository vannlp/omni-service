<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\V1\Models\AbstractModel;

class NinjaSyncListLiveModel extends AbstractModel
{
    public function __construct(NinjaSyncListLiveModel $ninjaSyncListLiveModel = null)
    {
        parent::__construct($ninjaSyncListLiveModel);
    }

    public function upsert($input)
    {
        $companyId              = !empty($input['company_id']) ? $input['company_id'] : null;
        $param                  = [
            'code'         => $input['code'],
            'user_name'    => $input['user_name'],
            'phone'        => !empty($input['phone']) ? $input['phone'] : null,
            'comment'      => !empty($input['comment']) ? $input['comment'] : null,
            'created_date' => !empty($input['created_date']) ? $input['created_date'] : null,
            'company_id'   => $companyId,
        ];
        $ninjaSyncListLiveModel = $this->create($param);

        return $ninjaSyncListLiveModel;
    }
}


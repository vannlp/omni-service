<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\V1\Models\AbstractModel;

class NinjaSyncFilterInteractiveModel extends AbstractModel
{
    public function __construct(NinjaSyncFilterInteractiveModel $ninjaSyncFilterInteractiveModel = null)
    {
        parent::__construct($ninjaSyncFilterInteractiveModel);
    }

    public function upsert($input)
    {
        $companyId                       = !empty($input['company_id']) ? $input['company_id'] : null;
        $param                           = [
            'code'        => $input['code'],
            'user_name'   => $input['user_name'],
            'gender'      => !empty($input['gender']) ? $input['gender'] : null,
            'location'    => !empty($input['location']) ? $input['location'] : null,
            'interactive' => $input['interactive'],
            'company_id'  => $companyId,
        ];
        $ninjaSyncFilterInteractiveModel = $this->create($param);

        return $ninjaSyncFilterInteractiveModel;
    }
}



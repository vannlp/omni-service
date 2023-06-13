<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;
use App\V1\Models\AbstractModel;

class NinjaSyncListGroupModel extends AbstractModel
{
    public function __construct(NinjaSyncListGroupModel $NinjaSyncListGroupModel = null)
    {
        parent::__construct($NinjaSyncListGroupModel);
    }

    public function upsert($input)
    {
        $companyId               = !empty($input['company_id']) ? $input['company_id'] : null;
        $param                   = [
            'code'       => $input['code'],
            'name'       => $input['name'],
            'status'     => !empty($input['status']) ? $input['status'] : null,
            'location'   => !empty($input['location']) ? $input['location'] : null,
            'member'     => !empty($input['member']) ? $input['member'] : null,
            'pending'    => !empty($input['pending']) ? $input['pending'] : null,
            'company_id' => $companyId,
        ];
        $NinjaSyncListGroupModel = $this->create($param);

        return $NinjaSyncListGroupModel;
    }
}

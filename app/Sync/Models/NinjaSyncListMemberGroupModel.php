<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;
use App\V1\Models\AbstractModel;

class NinjaSyncListMemberGroupModel extends AbstractModel
{
    public function __construct(NinjaSyncListMemberGroupModel $ninjaSyncListMemberGroupModel = null)
    {
        parent::__construct($ninjaSyncListMemberGroupModel);
    }

    public function upsert($input)
    {
        $companyId               = !empty($input['company_id']) ? $input['company_id'] : null;
        $param                   = [
            'code'       => $input['code'],
            'name'       => $input['name'],
            'admin'     => !empty($input['admin']) ? $input['admin'] : null,
            'location'   => !empty($input['location']) ? $input['location'] : null,
            'gender'     => !empty($input['gender']) ? $input['gender'] : null,
            'company_id' => $companyId,
        ];
        $ninjaSyncListMemberGroupModel = $this->create($param);

        return $ninjaSyncListMemberGroupModel;
    }
}

<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\V1\Models\AbstractModel;

class NinjaSyncUidAnalysisModel extends AbstractModel
{
    public function __construct(NinjaSyncUidAnalysisModel $ninjaSyncUidAnalysisModel = null)
    {
        parent::__construct($ninjaSyncUidAnalysisModel);
    }

    public function upsert($input)
    {
        $companyId               = !empty($input['company_id']) ? $input['company_id'] : null;
        $param                   = [
            'code'       => $input['code'],
            'name'       => $input['user_name'],
            'gender'     => !empty($input['gender']) ? $input['gender'] : null,
            'country'   => !empty($input['country']) ? $input['country'] : null,
            'nation'   => !empty($input['nation']) ? $input['nation'] : null,
            'friend'     => !empty($input['friend']) ? $input['friend'] : null,
            'company_id' => $companyId,
        ];
        $ninjaSyncUidAnalysisModel = $this->create($param);

        return $ninjaSyncUidAnalysisModel;
    }
}



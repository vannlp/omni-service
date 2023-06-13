<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\V1\Models\AbstractModel;

class NinjaSyncUidPostModel extends AbstractModel
{
    public function __construct(NinjaSyncUidPostModel $ninjaSyncUidPostModel = null)
    {
        parent::__construct($ninjaSyncUidPostModel);
    }

    public function upsert($input)
    {
        $companyId                = !empty($input['company_id']) ? $input['company_id'] : null;
        $param                    = [
            'like'         => !empty($input['like']) ? $input['like'] : null,
            'comment'         => !empty($input['comment']) ? $input['comment'] : null,
            'share'      => !empty($input['share']) ? $input['share'] : null,
            'company_id'   => $companyId,
        ];
        $ninjaSyncUidPostModel = $this->create($param);

        return $ninjaSyncUidPostModel;
    }
}


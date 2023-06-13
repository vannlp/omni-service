<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\V1\Models\AbstractModel;

class NinjaSyncListPageIdModel extends AbstractModel
{
    public function __construct(NinjaSyncListPageIdModel $ninjaSyncListPageIdModel = null)
    {
        parent::__construct($ninjaSyncListPageIdModel);
    }

    public function upsert($input)
    {
        $companyId                       = !empty($input['company_id']) ? $input['company_id'] : null;
        $param                           = [
            'code'        => $input['code'],
            'name'   => $input['name'],
            'like'      => !empty($input['like']) ? $input['like'] : null,
            'follow'      => !empty($input['follow']) ? $input['follow'] : null,
            'checkin'      => !empty($input['checkin']) ? $input['checkin'] : null,
            'email'      => !empty($input['email']) ? $input['email'] : null,
            'location'      => !empty($input['location']) ? $input['location'] : null,
            'category'      => !empty($input['category']) ? $input['category'] : null,
            'created_date'    => !empty($input['created_date']) ? $input['created_date'] : null,
            'company_id'  => $companyId,
        ];
        $ninjaSyncListPageIdModel = $this->create($param);

        return $ninjaSyncListPageIdModel;
    }
}



<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\V1\Models\AbstractModel;

class NinjaSyncFilterPostModel extends AbstractModel
{
    public function __construct(NinjaSyncFilterPostModel $ninjaSyncFilterPostModel = null)
    {
        parent::__construct($ninjaSyncFilterPostModel);
    }

    public function upsert($input)
    {
        $companyId                = !empty($input['company_id']) ? $input['company_id'] : null;
        $param                    = [
            'code'         => $input['code'],
            'post'         => $input['post'],
            'content'      => !empty($input['content']) ? $input['content'] : null,
            'created_date' => !empty($input['created_date']) ? $input['created_date'] : null,
            'company_id'   => $companyId,
        ];
        $ninjaSyncFilterPostModel = $this->create($param);

        return $ninjaSyncFilterPostModel;
    }
}


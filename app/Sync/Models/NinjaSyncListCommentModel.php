<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\V1\Models\AbstractModel;

class NinjaSyncListCommentModel extends AbstractModel
{
    public function __construct(NinjaSyncListCommentModel $ninjaSyncListCommentModel = null)
    {
        parent::__construct($ninjaSyncListCommentModel);
    }

    public function upsert($input)
    {
        $companyId                 = !empty($input['company_id']) ? $input['company_id'] : null;
        $param                     = [
            'user'       => $input['user'],
            'code'       => $input['code'],
            'name'       => !empty($input['name']) ? $input['name'] : null,
            'post_id'    => !empty($input['post_id']) ? $input['post_id'] : null,
            'comment'    => $input['comment'],
            'company_id' => $companyId,
        ];
        $ninjaSyncListCommentModel = $this->create($param);

        return $ninjaSyncListCommentModel;
    }
}



<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\NinjaSync;
use App\Supports\Message;
use App\TM;
use App\User;
use App\V1\Models\AbstractModel;

class NinjaSyncModel extends AbstractModel
{
       public function __construct(NinjaSync $NinjaSync = null)
       {
              parent::__construct($NinjaSync);
       }
       public function upsert($input)
       {
              $id = !empty($input['id']) ? $input['id'] : 0;
              $companyId = !empty($input['company_id']) ? $input['company_id'] : null;
                     $param = [
                            'name'     => $input['name'],
                            'name_post'     => $input['name_post'],
                            'reaction' => $input['reaction'],
                            'comment' => $input['comment'],
                            'share' => $input['share'],
                            'company_id'  => $companyId,
                     ];
                     $ninjaSync = $this->create($param);
              
              return $ninjaSync;
       }
}

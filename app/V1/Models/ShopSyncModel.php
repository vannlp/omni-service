<?php

/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:48 AM
 */

namespace App\V1\Models;


use App\ShopSync;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Arr;

class ShopSyncModel extends AbstractModel
{
     public function __construct(ShopSync $model = null)
     {
          parent::__construct($model);
     }
}

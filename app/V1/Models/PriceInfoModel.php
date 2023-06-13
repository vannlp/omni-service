<?php

namespace App\V1\Models;

use App\Module;
use App\NewsPost;
use App\PriceInfo;
use App\Supports\Message;
use App\TM;

class PriceInfoModel extends AbstractModel
{
    public function __construct(PriceInfo $model = null)
    {
        parent::__construct($model);
    }


}

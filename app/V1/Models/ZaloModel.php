<?php

namespace App\V1\Models;

use App\Zalo;

class ZaloModel extends AbstractModel
{
    public function __construct(Zalo $model = null)
    {
        parent::__construct($model);
    }
}

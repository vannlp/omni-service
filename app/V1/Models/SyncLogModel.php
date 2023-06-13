<?php

namespace App\V1\Models;

use App\SyncLog;

class SyncLogModel extends AbstractModel
{
    public function __construct(SyncLog $model = null)
    {
        parent::__construct($model);
    }
}

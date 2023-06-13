<?php

namespace App\V1\Transformers\Zalo;

use App\SyncLog;
use League\Fractal\TransformerAbstract;

class ZaloSyncLogTransformer extends TransformerAbstract
{
    public function transform(SyncLog $log)
    {
        return [
            'store_id' => $log->store_id,
            'from'     => $log->from,
            'type'     => $log->type,
            'issue'    => $log->issue
        ];
    }
}

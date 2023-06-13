<?php

namespace App\V1\Transformers\Zalo;

use App\Zalo;
use League\Fractal\TransformerAbstract;

class ZaloTransformer extends TransformerAbstract
{
    public function transform(Zalo $zalo)
    {
        return [
            'store_id'          => $zalo->store_id,
            'zalo_access_token' => $zalo->zalo_access_token,
            'zalo_oaid'         => $zalo->zalo_oaid
        ];
    }
}

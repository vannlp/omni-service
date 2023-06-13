<?php

/**
 * User: kpistech2
 * Date: 2020-07-04
 * Time: 02:51
 */

namespace App\V1\Transformers\Setting;


use App\Setting;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class SettingClientTransformer extends TransformerAbstract
{
    public function transform(Setting $item)
    {
        try {
            return [
                'id'          => $item->id,
                'code'        => $item->code,
                'data'       => json_decode($item->data_client, true),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

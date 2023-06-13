<?php

/**
 * Created by PhpStorm.
 * User: SANG NGUYEN
 * Date: 2/24/2019
 * Time: 4:07 PM
 */

namespace App\V1\Transformers\CDP;

use App\CdpLogs;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class CdpLogsTransformer extends TransformerAbstract
{
    public function transform(CdpLogs $log)
    {
        try {
         
            return [
                'id'                            => $log->id,
                // 'order_code'                  => !empty($log->order_code) ? $log->order_code : null,
                // 'params'                        => $log->param,
                'params'                        => $log->param_request,
                'code'                          => $log->code,
                'content'                       => $log->content,
                'response'                      => $log->response,
                'sync_type'                     => $log->sync_type,
                'count_respost'                 => $log->count_repost,
                'status'                        => strtolower($log->status),
                'created_at'                    => !empty($log->created_at) ? date(
                    'd-m-Y H:i:s',
                    strtotime($log->created_at)
                ) : null,
                'updated_at'                  => !empty($log->updated_at) ? date(
                    'd-m-Y H:i:s',
                    strtotime($log->updated_at)
                ) : null,

            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

<?php


namespace App\V1\Transformers\CallHistory;


use App\CallHistory;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class CallHistoryReportTransformer extends TransformerAbstract
{
    public function transform(CallHistory $model)
    {
        try {
            return [
                'receiver_name'  => $model->receiver_name,
                'total_call'     => $model->total_call,
                'total_vote'     => round($model->total_vote / $model->total_call, 1),
                'user_id'        => $model->user_id,
                'email'          => $model->email
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);

            throw new \Exception($response['message'], $response['code']);
        }
    }
}
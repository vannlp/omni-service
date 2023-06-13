<?php


namespace App\V1\Transformers\CallHistory;


use App\CallHistory;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class CallHistoryTransformer extends TransformerAbstract
{
    public function transform(CallHistory $model)
    {
        try {
            return [
                'id'             => $model->id,
                'caller_id'      => $model->caller_id,
                'receiver_id'    => $model->receiver_id,
                'call_from_time' => $model->call_from_time,
                'call_end_time'  => $model->call_end_time,
                'total_time'     => !empty($model->total_time) ? $model->total_time : "00:00:00",
                'vote'           => $model->vote,
                'caller_name'    => object_get($model,'callerId.profile.full_name',null),
                'receiver_name'  => object_get($model,'receiverId.profile.full_name',null),
                'is_active'      => $model->is_active,
                'created_at'     => date('d-m-Y', strtotime($model->created_at)),
                'updated_at'     => date('d-m-Y', strtotime($model->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
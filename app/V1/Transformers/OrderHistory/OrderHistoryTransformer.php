<?php


namespace App\V1\Transformers\OrderHistory;

use App\Supports\TM_Error;
use App\OrderHistory;
use League\Fractal\TransformerAbstract;

class OrderHistoryTransformer extends TransformerAbstract
{
    public function transform(OrderHistory $orderHistory)
    {
        try {

            return [
                'order_id'   => $orderHistory->order_id,
                'order_code' => object_get($orderHistory, 'order.code', null),
                'status'     => $orderHistory->status,
                'created_at' => $orderHistory->created_at,
                'created_by' => object_get($orderHistory, "createdBy.profile.full_name", null),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
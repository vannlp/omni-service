<?php
/**
 * User: dai.ho
 * Date: 9/07/2020
 * Time: 10:57 AM
 */

namespace App\V1\Transformers\PromotionProgram;

use App\PromotionTotal;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class PromotionTotalTransformer extends TransformerAbstract
{
    public function transform(PromotionTotal $model)
    {
        try {
            return [
                'id'      => $model->id,
                'cart_id' => $model->cart_id,

                'order_id'            => $model->order_id,
                'order_code'          => object_get($model, 'order.code'),
                'order_customer_id'   => object_get($model, 'order.customer_id'),
                'order_customer_code' => object_get($model, 'order.customer.code'),
                'order_customer_name' => object_get($model, 'order.customer.name'),

                'promotion_code'         => $model->promotion_code,
                'promotion_name'         => $model->promotion_name,
                'promotion_type'         => $model->promotion_type,
                'promotion_act_approval' => $model->promotion_act_approval,
                'value'                  => $model->value,

                'approval_status' => $model->approval_status,

                'created_at' => date('d-m-Y', strtotime($model->created_at)),
                'updated_at' => date('d-m-Y', strtotime($model->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

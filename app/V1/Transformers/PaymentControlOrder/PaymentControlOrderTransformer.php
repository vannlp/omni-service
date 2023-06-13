<?php
/**
 * User: kpistech2
 * Date: 2020-07-02
 * Time: 22:39
 */

namespace App\V1\Transformers\PaymentControlOrder;


use App\PaymentControlOrder;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class PaymentControlOrderTransformer extends TransformerAbstract
{
    public function transform(PaymentControlOrder $item)
    {
        try {
            return [
                'id'             => $item->id,
                'order_id'       => $item->order_id,
                'order_code'     => $item->order_code,
                'order_price'    => $item->order_price,
                'payment_price'  => $item->payment_price,
                'price_diff'     => $item->price_diff,
                'control_date'   => date('d-m-Y', strtotime($item->control_date)),
                'payment_type'   => $item->payment_type,
                'account_name'   => $item->account_name,
                'account_number' => $item->account_number,
                'payment_date'   => !empty($item->payment_date) ? date('d-m-Y', strtotime($item->payment_date)) : null,

                'store_id'   => $item->store_id,
                'store_code' => object_get($item, 'store.code'),
                'store_name' => object_get($item, 'store.name'),

                'company_id'   => $item->store_id,
                'company_code' => object_get($item, 'company.code'),
                'company_name' => object_get($item, 'company.name'),

                'is_active'  => $item->is_active,
                'created_at' => date('d-m-Y', strtotime($item->created_at)),
                'updated_at' => date('d-m-Y', strtotime($item->updated_at)),
                'created_by' => object_get($item, 'createdBy.full_name', null),
                'updated_by' => object_get($item, 'updatedBy.full_name', null),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

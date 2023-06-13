<?php
/**
 * User: dai.ho
 * Date: 3/06/2020
 * Time: 2:25 PM
 */

namespace App\V1\Transformers\EnterpriseOrder;


use App\EnterpriseOrder;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class EnterpriseOrderTransformer extends TransformerAbstract
{
    public function transform(EnterpriseOrder $item)
    {
        try {
            return [
                'id'               => $item->id,
                'code'             => $item->code,
                'status'           => $item->status,
                'status_name'      => ORDER_STATUS_NAME[$item->status],
                'enterprise_id'    => $item->customer_id,
                'enterprise_name'  => object_get($item, 'enterprise.profile.full_name'),
                'enterprise_code'  => object_get($item, 'enterprise.code'),
                'enterprise_email' => object_get($item, 'enterprise.email'),
                'enterprise_phone' => object_get($item, 'enterprise.phone'),
                'note'             => $item->note,
                'updated_date'     => !empty($item->updated_date) ? date("d-m-Y",
                    strtotime($item->updated_date)) : null,
                'created_date'     => date("d-m-Y H:i", strtotime($item->created_date)),
                'completed_date'   => !empty($item->completed_date) ? date("d-m-Y H:i",
                    strtotime($item->completed_date)) : null,
                'latlong'          => $item->latlong,
                'lat'              => (float)$item->lat,
                'long'             => (float)$item->long,
                'details'          => array_map(function ($detail) {
                    return [
                        'enterprise_order_id' => $detail['enterprise_order_id'],
                        'order_detail_id'     => $detail['order_detail_id'],
                        'product_id'          => $detail['product_id'],
                        'qty'                 => $detail['qty'],
                        'price'               => $detail['price'],
                        'real_price'          => $detail['real_price'],
                        'price_down'          => $detail['price_down'],
                        'total'               => $detail['total'],
                        'note'                => $detail['note'],
                    ];
                }, $item->details->toArray()),
                'created_at'       => date("d-m-Y H:i", strtotime($item->created_at)),
                'created_by'       => object_get($item, 'created_by.profile.full_name'),
                'updated_at'       => date("d-m-Y", strtotime($item->updated_at)),
                'updated_by'       => object_get($item, 'updated_by.profile.full_name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

<?php
/**
 * User: dai.ho
 * Date: 29/06/2020
 * Time: 1:18 PM
 */

namespace App\V1\Transformers\ShippingOrder;


use App\ShippingOrder;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class ShippingOrderTransformer extends TransformerAbstract
{
    public function transform(ShippingOrder $item)
    {
        try {
            return [
                'id'                     => $item->id,
                'type'                   => $item->type,
                'code'                   => $item->code,
                'name'                   => $item->name,
                'status'                 => $item->status,
                'status_text'            => $item->status_text,
                'description'            => $item->description,
                'partner_id'             => $item->partner_id,
                'delivery_status'        => $item->delivery_status,
                'estimated_pick_time'    => $item->estimated_pick_time,
                'estimated_deliver_time' => $item->estimated_deliver_time,
                'free_ship'              => $item->free_ship,
                'transport'              => $item->transport,
                'ship_fee'               => $item->ship_fee,
                'pick_money'             => $item->pick_money,
                'count_print'            => Arr::get($item, 'count_print', 0),
                'distributor_code'       => Arr::get($item, 'order.distributor_code', null),
                'distributor_name'       => Arr::get($item, 'order.distributor_name', null),
                'result_json'            => !empty($item->result_json) ? json_decode($item->result_json, true) : $item->result_json,

                'count_details' => count($item->details),
                'company_id'    => $item->company_id,
                'company_code'  => array_get($item, 'company.code'),
                'company_name'  => array_get($item, 'company.name'),

                'order_id'   => $item->order_id,
                'order_code' => array_get($item, 'order.code'),

                'delivery_status_histories' => array_get($item, 'order.shippingStatusHistories'),


                'sync_status'      => $sync_status = array_get($item, 'order.sync_status'),
                'sync_status_name' => SYNC_STATUS_NAME[$sync_status] ?? null,

                'customer_name' => array_get($item, 'order.customer_name'),
                'code_type_ghn' =>  $item->code_type_ghn,
                'is_active'  => $item->is_active,
                'created_at' => date('d-m-Y H:i', strtotime($item->created_at)),
                'updated_at' => date('d-m-Y H:i', strtotime($item->updated_at)),
                'created_by' => object_get($item, 'createdBy.full_name', null),
                'updated_by' => object_get($item, 'updatedBy.full_name', null),
            ];
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

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
use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;

class ShippingOrderDetailTransformer extends TransformerAbstract
{
    public function transform(ShippingOrder $item)
    {

        $is_log_send_dms = DB::table('log_send_dms')
        ->where('code', $item->code)
        ->where(  function($q ) {
            $q->where('response', '!=', '[]')
            ->orWhereNotNull('response');
        })->exists();

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
                'estimated_pick_time'    => $item->estimated_pick_time,
                'estimated_deliver_time' => $item->estimated_deliver_time,
                'transport'              => $item->transport,
                'free_ship'              => $item->free_ship,
                'ship_fee'               => $item->ship_fee,
                'pick_money'             => $item->pick_money,
                'result_json'            => !empty($item->result_json) ? json_decode($item->result_json, true) : $item->result_json,

                'company_id'   => $item->company_id,
                'company_code' => array_get($item, 'company.code'),
                'company_name' => array_get($item, 'company.name'),

                'order_id'         => $item->order_id,
                'order_code'       => array_get($item, 'order.code'),
                'shipping_method'       => array_get($item, 'order.shipping_method'),
                'shipping_method_code'       => array_get($item, 'order.shipping_method_code'),
                'shipping_method_name'       => array_get($item, 'order.shipping_method_name'),
                'shipping_service'       => array_get($item, 'order.shipping_service'),
                'shipping_service_name'       => array_get($item, 'order.shipping_service_name'),
                'ship_fee'       => array_get($item, 'order.ship_fee'),
                'sync_status'      => $sync_status = array_get($item, 'order.sync_status'),
                'sync_status_name' => SYNC_STATUS_NAME[$sync_status] ?? null,
                'count_print'      => Arr::get($item, 'count_print', 0),
                'is_log_send_dms'  => $is_log_send_dms,
                'details'          => array_map(function ($item) {
                    return [
                        'order_detail_id' => $item['id'],

                        'product_id'   => $item['product_id'],
                        'product_code' => $item['product_code'],
                        'product_name' => $item['product_name'],

                        'unit_id'   => $item['unit_id'],
                        'unit_code' => $item['unit_code'],
                        'unit_name' => $item['unit_name'],

                        'warehouse_id'   => $item['warehouse_id'],
                        'warehouse_code' => $item['warehouse_code'],
                        'warehouse_name' => $item['warehouse_name'],
                        'batch_id'   => $item['batch_id'],
                        'batch_code' => $item['batch_code'],
                        'batch_name' => $item['batch_name'],
                    
                        'qty'         => $item['qty'],
                        'ship_qty'    => $item['ship_qty'],
                        'shipped_qty' => $item['shipped_qty'],
                        'waiting_qty' => $item['waiting_qty'],
                        'price'       => $item['price'],
                        'total_price' => $item['total_price'],
                        'discount'    => $item['discount'],
                    ];
                }, $item->details->toArray()),

                'order'      => array_merge($item->order->toArray(), ['payment_method_name' => PAYMENT_METHOD_NAME[$item->order->payment_method] ?? null]),
                'is_active'  => $item->is_active,
                'created_at' => date('d-m-Y', strtotime($item->created_at)),
                'updated_at' => date('d-m-Y', strtotime($item->updated_at)),
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

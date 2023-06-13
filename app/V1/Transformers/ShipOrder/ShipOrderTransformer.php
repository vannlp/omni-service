<?php


namespace App\V1\Transformers\ShipOrder;


use App\ShipOrder;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class ShipOrderTransformer extends TransformerAbstract
{
    public function transform(ShipOrder $shipOrder)
    {
        $details = $shipOrder->details;
        $shipOrderDetails = [];
        foreach ($details as $detail) {
            $shipOrderDetails[] = [
                'id'                      => $detail['id'],
                'ship_id'                 => $detail['ship_id'],
                'order_detail_id'         => $detail['order_detail_id'],
                'company_id'              => $detail['company_id'],
                'product_id'              => $detail['product_id'],
                'product_code'            => $detail['product_code'],
                'product_name'            => $detail['product_name'],
                'warehouse_id'            => $detail['warehouse_id'],
                'warehouse_code'          => $detail['warehouse_code'],
                'warehouse_name'          => $detail['warehouse_name'],
                'batch_id'                => $detail['batch_id'],
                'batch_code'              => $detail['batch_code'],
                'batch_name'              => $detail['batch_name'],
                'product_unit'            => array_get($detail, 'product_unit', null),
                'product_unit_name'       => array_get($detail, 'product_unit_name', null),
                'store_id'                => $detail['store_id'],
                'count_qty_ship'          => array_get($detail, 'count_qty_ship', null),
                'sum_qty_product_shipped' => array_get($detail, 'sum_qty_product_shipped', null),
                'available_qty'           => array_get($detail, 'available_qty', null),
                'ship_qty'                => array_get($detail, 'ship_qty', null),
                'shipped_qty'             => array_get($detail, 'shipped_qty', null),
                'qty'                     => array_get($detail, 'qty', null),
                'price'                   => array_get($detail, 'price', null),
                'is_active'               => $detail['is_active'],
                'discount'                => array_get($detail, 'discount', null),
                'total'                   => array_get($detail, 'total', null),
                'item_id'                 => array_get($detail, 'item_id', null),
            ];
        }
        try {
            return [
                'id'                             => $shipOrder->id,
                'code'                           => object_get($shipOrder, 'code', null),
                'order_code'                     => $shipOrder->order_code,
                'order_id'                       => $shipOrder->order_id,
                'status'                         => object_get($shipOrder, 'status', null),
                'company_id'                     => $shipOrder->company_id,
                'store_id'                       => $shipOrder->store_id,
                'customer_id'                    => object_get($shipOrder, 'customer_id', null),
                'customer_name'                  => object_get($shipOrder, 'customer_name', null),
                'customer_code'                  => object_get($shipOrder, 'customer_code', null),
                'customer_email'                 => object_get($shipOrder, 'customer_email', null),
                'customer_phone'                 => object_get($shipOrder, 'customer_phone', null),
                'created_date'                   => !empty($shipOrder->created_date) ? date('d-m-Y',
                    strtotime($shipOrder->created_date)) : null,
                'approver'                       => $shipOrder->approver,
                'approver_name'                  => object_get($shipOrder, 'approver_name', null),
                'qty_equal'                      => object_get($shipOrder, 'qty_equal', null),
                'count_qty_ship'                 => object_get($shipOrder, 'count_qty_ship', null),
                'description'                    => object_get($shipOrder, 'description', null),
                'shipping_address'               => object_get($shipOrder, 'shipping_address', null),
                'real_date'                      => !empty($shipOrder->real_date) ? date('d-m-Y',
                    strtotime($shipOrder->real_date)) : null,
                'total_price'                    => object_get($shipOrder, 'total_price', null),
                'qty_equal_shipped_order'        => object_get($shipOrder, 'qty_equal_shipped_order', null),
                'payment_method'                 => object_get($shipOrder, 'payment_method', null),
                'payment_method_name'            => object_get($shipOrder, 'payment_method_name', null),
                'status_name'                    => object_get($shipOrder, 'status_name', null),
                'payment_status_name'            => object_get($shipOrder, 'payment_status_name', null),
                'payment_status'                 => object_get($shipOrder, 'payment_status', null),
                'shipping_address_full_name'     => object_get($shipOrder, 'shipping_address_full_name', null),
                'shipping_address_phone'         => object_get($shipOrder, 'shipping_address_phone', null),
                'street_address'                 => object_get($shipOrder, 'street_address', null),
                'shipping_address_city_code'     => object_get($shipOrder, 'shipping_address_city_code', null),
                'shipping_address_city'          => object_get($shipOrder, 'shipping_address_city', null),
                'shipping_address_district_code' => object_get($shipOrder, 'shipping_address_district_code', null),
                'shipping_address_district'      => object_get($shipOrder, 'shipping_address_district', null),
                'shipping_address_ward_code'     => object_get($shipOrder, 'shipping_address_ward_code', null),
                'shipping_address_ward'          => object_get($shipOrder, 'shipping_address_ward', null),
                'exp'                            => !empty($shipOrder->exp) ? date('d-m-Y',
                    strtotime($shipOrder->exp)) : null,
                'mfg'                            => !empty($shipOrder->mfg) ? date('d-m-Y',
                    strtotime($shipOrder->mfg)) : null,
                'is_active'                      => object_get($shipOrder, 'is_active', null),
                'details'                        => !empty($shipOrderDetails) ? $shipOrderDetails : null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
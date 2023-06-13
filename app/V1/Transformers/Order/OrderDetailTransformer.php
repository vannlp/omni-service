<?php


namespace App\V1\Transformers\Order;


use App\OrderDetail;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class OrderDetailTransformer extends TransformerAbstract
{
    public function transform(OrderDetail $orderDetail)
    {
        try {
            $fileCode = object_get($orderDetail, 'product.file.code');

            $priceDown = object_get($orderDetail, 'product.price_down', 0);
            return [
                'id'                 => $orderDetail->id,
                'order_id'           => $orderDetail->order_id,
                'product_id'         => $orderDetail->product_id,
                'product_code'       => object_get($orderDetail, 'product.code'),
                'product_name'       => object_get($orderDetail, 'product.name'),
                'thumbnail'          => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                'product_price_down' => $priceDown,
                'product_down_rate'  => $orderDetail->price != 0 ? $priceDown * 100 / $orderDetail->price : 0,
                'product_down_from'  => object_get($orderDetail, 'product.down_from'),
                'product_down_to'    => object_get($orderDetail, 'product.down_to'),
                'qty'                => $orderDetail->qty,
                'price'              => $orderDetail->price,
                'price_down'         => $orderDetail->price_down,
                'real_price'         => $orderDetail->real_price,
                'total'              => $orderDetail->total,
                'note'               => $orderDetail->note,
                'status'             => $orderDetail->status,
                'status_name'        => ORDER_STATUS_NAME[$orderDetail->status] ?? null,
                'next_status'        => NEXT_STATUS_ORDER[$orderDetail->status] ?? null,
                'next_status_name'   => !empty(NEXT_STATUS_ORDER[$orderDetail->status]) ? ORDER_STATUS_NAME[NEXT_STATUS_ORDER[$orderDetail->status]] : null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
<?php


namespace App\V1\Transformers\Order;


use App\Order;
use App\OrderDetail;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class OrderProductPurchasedTransformer extends TransformerAbstract
{
    public function transform(OrderDetail $orderDetail)
    {
        try {
            $fileCode = object_get($orderDetail, 'product.file.code');
            return [
                'id'         => $orderDetail->product_id,
                'name'       => object_get($orderDetail, 'product.name'),
                'slug'       => object_get($orderDetail, 'product.slug'),
                'price'      => object_get($orderDetail, 'product.price'),
                'thumbnail'  => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                'qty'        => object_get($orderDetail, 'qty')
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

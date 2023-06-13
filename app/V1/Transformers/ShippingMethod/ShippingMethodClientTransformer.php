<?php
/**
 * User: dai.ho
 * Date: 8/06/2020
 * Time: 3:42 PM
 */

namespace App\V1\Transformers\ShippingMethod;


use App\ShippingMethod;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class ShippingMethodClientTransformer extends TransformerAbstract
{
    public function transform(ShippingMethod $item)
    {
        try {
            return [
                'id'              => $item->id,
                'code'            => $item->code,
                'name'            => $item->name,
                'description'     => $item->description,
                'price'           => $item->price,
                'price_formatted' => $item->price > 0 ? number_format($item->price) . " đ" : $item->price,
                'created_at' => date('d-m-Y', strtotime($item->created_at)),
                'updated_at' => date('d-m-Y', strtotime($item->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

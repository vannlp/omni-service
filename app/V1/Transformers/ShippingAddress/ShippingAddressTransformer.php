<?php


namespace App\V1\Transformers\ShippingAddress;


use App\ShippingAddress;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class ShippingAddressTransformer extends TransformerAbstract
{
    public function transform(ShippingAddress $shippingAddress)
    {
        try {
            return [
                'id'             => $shippingAddress->id,
                'user_id'        => $shippingAddress->user_id,
                'user_name'      => object_get($shippingAddress, 'getUser.profile.full_name', null),
                'full_name'      => $shippingAddress->full_name,
                'phone'          => $shippingAddress->phone,
                'city_code'      => $shippingAddress->city_code,
                'city_name'      => object_get($shippingAddress, 'getCity.full_name', null),
                'district_code'  => $shippingAddress->district_code,
                'district_name'  => object_get($shippingAddress, 'getDistrict.full_name', null),
                'ward_code'      => $shippingAddress->ward_code,
                'ward_name'      => object_get($shippingAddress, 'getWard.full_name', null),
                'street_address' => $shippingAddress->street_address,
                'full_address'   => $shippingAddress->street_address.", ".object_get($shippingAddress, 'getWard.full_name', null).", ".object_get($shippingAddress, 'getDistrict.full_name', null).",".object_get($shippingAddress, 'getCity.full_name', null),
                'is_default'     => $shippingAddress->is_default,
                'created_at'     => date('d-m-Y', strtotime($shippingAddress->created_at)),
                'updated_at'     => date('d-m-Y', strtotime($shippingAddress->updated_at)),
                'created_by'     => object_get($shippingAddress, 'createdBy.profile.full_name', null),
                'updated_by'     => object_get($shippingAddress, 'updatedBy.profile.full_name', null)
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
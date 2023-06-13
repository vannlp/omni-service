<?php
/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:47 AM
 */

namespace App\V1\Transformers\CustomerInformation;


use App\CustomerInformation;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class CustomerInformationTransformer extends TransformerAbstract
{
    public function transform(CustomerInformation $item)
    {
        try {
            return [
                'id'             => $item->id,
                'name'           => $item->name ?? null,
                'phone'          => $item->phone ?? null,
                'address'        => $item->address ?? null,
                'city_id'        => Arr::get($item, 'city.id', null),
                'city_code'      => Arr::get($item, 'city_code', null),
                'city_name'      => Arr::get($item, 'city.name', null),
                'district_id'    => Arr::get($item, 'district.id', null),
                'district_code'  => Arr::get($item, 'district_code', null),
                'district_name'  => Arr::get($item, 'district.name', null),
                'ward_id'        => Arr::get($item, 'ward.id', null),
                'ward_code'      => Arr::get($item, 'ward_code', null),
                'ward_name'      => Arr::get($item, 'ward.name', null),
                'store_id'       => $item->store_id ?? null,
                'full_address'   => $item->full_address ?? null,
                'street_address' => $item->street_address ?? null,
                'note'           => $item->note ?? null,
                'gender'         => $item->gender ?? null,
                'email'          => $item->email ?? null,
                'is_new'         => Arr::get($item, 'user.password') == 'FROM-IMPORT' || Arr::get($item, 'user.password') == 'NOT-VERIFY-ACCOUNT' ? 0 : 1,
                
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

<?php
/**
 * User: Ho Sy Dai
 * Date: 9/28/2018
 * Time: 10:27 AM
 */

namespace App\V1\Transformers\Setting;


use App\Supports\TM_Error;
use App\Ward;
use League\Fractal\TransformerAbstract;

class WardTransformer extends TransformerAbstract
{
    public function transform(Ward $ward)
    {
        try {
            return [
                'id'          => $ward->id,
                'code'        => $ward->code,
                'name'        => $ward->full_name,
                'description' => $ward->description,

                'district_code' => object_get($ward, 'district.code', null),
                'district_name' => object_get($ward, 'district.name', null),

                'city_code' => object_get($ward, 'district.city.code', null),
                'city_name' => object_get($ward, 'district.city.name', null),

                'country_id'   => object_get($ward, 'district.city.country.id', null),
                'country_name' => object_get($ward, 'district.city.country.name', null),

                'is_active'  => $ward->is_active,
                'updated_at' => !empty($ward->updated_at) ? date('d-m-Y', strtotime($ward->updated_at)) : null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

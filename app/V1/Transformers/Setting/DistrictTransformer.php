<?php
/**
 * User: Ho Sy Dai
 * Date: 9/28/2018
 * Time: 10:25 AM
 */

namespace App\V1\Transformers\Setting;


use App\District;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class DistrictTransformer extends TransformerAbstract
{
    public function transform(District $district)
    {
        try {
            return [
                'id'          => $district->id,
                'code'        => $district->code,
                'name'        => $district->full_name,
                'description' => $district->description,

                'city_code' => object_get($district, 'city.code', null),
                'city_name' => object_get($district, 'city.name', null),

                'country_id'   => object_get($district, 'city.country.id', null),
                'country_name' => object_get($district, 'city.country.name', null),

                'is_active'  => $district->is_active,
                'updated_at' => !empty($district->updated_at) ? date('d-m-Y', strtotime($district->updated_at)) : null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

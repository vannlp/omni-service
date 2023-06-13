<?php
/**
 * User: Ho Sy Dai
 * Date: 9/28/2018
 * Time: 10:24 AM
 */

namespace App\V1\Transformers\Setting;


use App\City;
use App\CityHasRegion;
use App\Supports\TM_Error;
use App\TM;
use League\Fractal\TransformerAbstract;

class CityTransformer extends TransformerAbstract
{
    public function transform(City $city)
    {
        try {
            $region = CityHasRegion::model()->where('code_city',$city->code)
                ->where('company_id',TM::getCurrentCompanyId())
                ->where('store_id',TM::getCurrentStoreId())->first();
            return [
                'id'          => $city->id,
                'code'        => $city->code,
                'name'        => $city->name,
                'full_name'        => $city->full_name,
                'description' => $city->description,
                'region_code' => $region->code_region ?? null,
                'region_name' => $region->name_region ?? null,
                'country_id'   => object_get($city, 'country_id', null),
                'country_name' => object_get($city, 'country.name', null),

                'is_active'  => $city->is_active,
                'updated_at' => !empty($city->updated_at) ? date('d-m-Y', strtotime($city->updated_at)) : null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

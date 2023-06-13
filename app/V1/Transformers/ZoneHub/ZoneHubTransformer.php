<?php


namespace App\V1\Transformers\ZoneHub;


use App\Supports\TM_Error;
use App\ZoneHub;
use League\Fractal\TransformerAbstract;

class ZoneHubTransformer extends TransformerAbstract
{
    public function transform(ZoneHub $zoneHub)
    {
        try {
            return [
                'id'                => $zoneHub->id,
                'name'              => $zoneHub->name,
                'latlong'           => $zoneHub->latlong,
                'company_id'        => $zoneHub->company_id,
                'description'       => $zoneHub->description,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
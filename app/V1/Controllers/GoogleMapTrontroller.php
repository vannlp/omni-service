<?php

namespace App\V1\Controllers;


use Illuminate\Http\Request;

class GoogleMapTrontroller extends BaseController
{
    public function geocoding(Request $request)
    {
        $input = $request->all();
        if (!empty($input['address'])) {
            $response = \GoogleMaps::load('geocoding')
                ->setParam(['address' => "{$input['address']}"])
                ->setEndpoint('json')
                ->get('results.geometry.location');
        }

        if (!empty($input['latlng'])) {
            $response = \GoogleMaps::load('geocoding')
                ->setParamByKey('latlng', "{$input['latlng']}")
                ->setEndpoint('json')
                ->get('results.formatted_address');
        }
        return $response ?? null;
    }
}
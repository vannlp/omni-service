<?php


namespace App\V1\Validators\ZoneHub;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ZoneHubUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'name'            => 'required|unique_update_company_delete:zone_hubs,name',
            'latlong'         => 'required',
            'description'     => 'required'
        ];

    }

    protected function attributes()
    {
        return [
            'code'            => Message::get("code"),
            'latlong'         => Message::get("latlong"),
            'description'     => Message::get("description"),
        ];
    }
}
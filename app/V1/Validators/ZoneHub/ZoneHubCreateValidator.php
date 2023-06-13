<?php


namespace App\V1\Validators\ZoneHub;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ZoneHubCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'name'        => 'required|unique_create_company_delete:zone_hubs,name',
            'latlong'     => 'required',
            'description' => 'required'
        ];

    }

    protected function attributes()
    {
        return [
            'name'        => Message::get("name"),
            'latlong'     => Message::get("latlong"),
            'description' => Message::get("description"),
        ];
    }
}
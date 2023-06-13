<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$api->get('countries', [
    'action' => '',
    'uses' => 'SettingController@getCountries',
]);

$api->get('/{country_id:[0-9]+}/cities', [
    'action' => '',
    'uses' => 'SettingController@getCity',
]);

$api->get('/{city_code:[0-9]+}/districts', [
    'action' => '',
    'uses' => 'SettingController@getDistrict',
]);

$api->get('{district_code:[0-9]+}/wards', [
    'action' => '',
    'uses' => 'SettingController@getWard',
]);

$api->put('cache/clear-all', [
    'action' => '',
    'uses' => 'SettingController@clearAllCacheRedis',
]);

$api->get('get-info-vpbank', [
    'action' => '',
    'uses' => 'SettingController@returnInfoVpbank',
]);
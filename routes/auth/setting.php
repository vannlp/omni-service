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
$api->get('/{country_id:[0-9]+}/cities', [
    'action' => '',
    'uses'   => 'SettingController@getCity',
]);

$api->get('/{city_code:[0-9]+}/districts', [
    'action' => '',
    'uses'   => 'SettingController@getDistrict',
]);

$api->get('{district_code:[0-9]+}/wards', [
    'action' => '',
    'uses'   => 'SettingController@getWard',
]);
$api->put('/{city_code:[0-9]+}/cities', [
    'action' => '',
    'uses'   => 'SettingController@updateCity',
]);

$api->get('/settings', [
    'action' => 'VIEW-SETTING',
    'uses'   => 'SettingController@search',
]);

$api->get('/settings/{code:[0-9a-zA-Z-_.]+}', [
    'action' => 'VIEW-SETTING',
    'uses'   => 'SettingController@detail',
]);

$api->post('/settings', [
    'action' => 'CREATE-SETTING',
    'uses'   => 'SettingController@store',
]);

$api->put('/settings/{code:[0-9a-zA-Z-_.]+}', [
    'action' => 'UPDATE-SETTING',
    'uses'   => 'SettingController@update',
]);

$api->delete('/settings/{code:[0-9a-zA-Z-_.]+}', [
    'action' => 'DELETE-SETTING',
    'uses'   => 'SettingController@delete',
]);

########################### NO AUTHENTICATION #####################
$api->get('/client/settings', [
    'name'   => 'SETTING-VIEW-LIST',
    'action' => '',
    'uses'   => 'SettingController@getClientSetting'
]);

$api->get('/client/settings/{code:[0-9a-zA-Z-_.]+}', [
    'name'   => 'SETTING-VIEW-DETAIL',
    'action' => '',
    'uses'   => 'SettingController@viewClientSetting'
]);

$api->get('/client/setting-by-slug/{slug}', [
    'name'   => 'SETTING-VIEW-DETAIL',
    'action' => '',
    'uses'   => 'SettingController@viewClientSettingBySlug'
]);

$api->get('/client/setting-by-slug-data-string/{slug}', [
    'name'   => 'SETTING-VIEW-DETAIL',
    'action' => '',
    'uses'   => 'SettingController@viewClientSettingBySlugDataString'
]);

$api->get('/client/data-first', [
    'name'   => 'SETTING-VIEW-DETAIL',
    'action' => '',
    'uses'   => 'SettingController@getDataFirst'
]);
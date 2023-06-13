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

$api->get('/areas', [
    'action' => 'VIEW-AREA',
    'uses'   => 'AreaController@search',
]);

$api->get('/areas/{id:[0-9]+}', [
    'action' => 'VIEW-AREA',
    'uses'   => 'AreaController@detail',
]);

$api->post('/areas', [
    'action' => 'CREATE-AREA',
    'uses'   => 'AreaController@store',
]);

$api->put('/areas/{id:[0-9]+}', [
    'action' => 'UPDATE-AREA',
    'uses'   => 'AreaController@update',
]);

$api->delete('/areas/{id:[0-9]+}', [
    'action' => 'DELETE-AREA',
    'uses'   => 'AreaController@delete',
]);

$api->get('/area-export-excel', [
    'action' => '',
    'uses'   => 'AreaController@areaExportExcel',
]);

########################### NO AUTHENTICATION #####################
$api->get('/client/areas', [
    'name'   => 'AREA-VIEW-LIST',
    'action' => '',
    'uses'   => 'AreaController@getClientArea'
]);
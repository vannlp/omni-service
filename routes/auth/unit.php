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

$api->get('/units', [
    'action' => 'VIEW-UNIT',
    'uses'   => 'UnitController@search',
]);

$api->get('/units/{id:[0-9]+}', [
    'action' => 'VIEW-UNIT',
    'uses'   => 'UnitController@detail',
]);

$api->post('/units', [
    'action' => 'CREATE-UNIT',
    'uses'   => 'UnitController@store',
]);

$api->put('/units/{id:[0-9]+}', [
    'action' => 'UPDATE-UNIT',
    'uses'   => 'UnitController@update',
]);

$api->delete('/units/{id:[0-9]+}', [
    'action' => 'DELETE-UNIT',
    'uses'   => 'UnitController@delete',
]);

$api->get('/unit-export-excel', [
    'action' => '',
    'uses'   => 'UnitController@unitExportExcel',
]);

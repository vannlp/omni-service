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

$api->get('/specifications', [
    'action' => 'VIEW-SPECIFICATION',
    'uses'   => 'SpecificationController@search',
]);

$api->get('/specification/{id:[0-9]+}', [
    'action' => 'VIEW-SPECIFICATION',
    'uses'   => 'SpecificationController@view',
]);

$api->post('/specification', [
    'action' => 'CREATE-SPECIFICATION',
    'uses'   => 'SpecificationController@create',
]);

$api->put('/specification/{id:[0-9]+}', [
    'action' => 'UPDATE-SPECIFICATION',
    'uses'   => 'SpecificationController@update',
]);

$api->delete('/specification/{id:[0-9]+}', [
    'action' => 'DELETE-SPECIFICATION',
    'uses'   => 'SpecificationController@delete',
]);

$api->get('/specification-export-excel', [
    'action' => '',
    'uses'   => 'SpecificationController@specificationExportExcel',
]);
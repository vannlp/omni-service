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

$api->get('/manufactures', [
//    'action' => 'VIEW-MANUFACTURE',
'uses' => 'ManufactureController@search',
]);

$api->get('/manufacture/{id:[0-9]+}', [
//    'action' => 'VIEW-MANUFACTURE',
'uses' => 'ManufactureController@detail',
]);

$api->post('/manufacture', [
//    'action' => 'CREATE-MANUFACTURE',
'uses' => 'ManufactureController@create',
]);

$api->put('/manufacture/{id:[0-9]+}', [
//    'action' => 'UPDATE-MANUFACTURE',
'uses' => 'ManufactureController@update',
]);

$api->delete('/manufacture/{id:[0-9]+}', [
//    'action' => 'DELETE-MANUFACTURE',
'uses' => 'ManufactureController@delete',
]);
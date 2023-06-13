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

$api->get('/ages', [
//    'action' => 'VIEW-AGE',
'uses' => 'AgeController@search',
]);

$api->get('/age/{id:[0-9]+}', [
//    'action' => 'VIEW-AGE',
'uses' => 'AgeController@detail',
]);

$api->post('/age', [
//    'action' => 'CREATE-AGE',
'uses' => 'AgeController@create',
]);

$api->put('/age/{id:[0-9]+}', [
//    'action' => 'UPDATE-AGE',
'uses' => 'AgeController@update',
]);

$api->delete('/age/{id:[0-9]+}', [
//    'action' => 'DELETE-AGE',
'uses' => 'AgeController@delete',
]);

########################### NO AUTHENTICATION #####################
$api->get('/client/ages', [
    'name' => 'AGE-VIEW-LIST',
    'uses' => 'AgeController@getListAge'
]);
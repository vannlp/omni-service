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

$api->get('/permissions', [
    'action' => 'VIEW-ROLE',
    'uses'   => 'PermissionController@search',
]);

$api->get('/permissions/{id:[0-9]+}', [
    'action' => 'VIEW-ROLE',
    'uses'   => 'PermissionController@detail',
]);

// Search All Group
$api->get('/permissions/groups', [
    'action' => 'VIEW-ROLE',
    'uses'   => 'PermissionController@allGroup'
]);
$api->post('/permissions', [
    'action' => 'CREATE-ROLE',
    'uses'   => 'PermissionController@create',
]);

$api->put('/permissions/{id:[0-9]+}', [
    'action' => 'UPDATE-ROLE',
    'uses'   => 'PermissionController@update',
]);

$api->delete('/permissions/{id:[0-9]+}', [
    'action' => 'DELETE-ROLE',
    'uses'   => 'PermissionController@delete',
]);
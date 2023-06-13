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

$api->get('/roles', [
    'action' => 'VIEW-ROLE',
    'uses' => 'RoleController@search',
]);

$api->get('/roles/{id:[0-9]+}', [
    'action' => 'VIEW-ROLE',
    'uses' => 'RoleController@detail',
]);

$api->post('/roles', [
    'action' => 'CREATE-ROLE',
    'uses' => 'RoleController@store',
]);

$api->put('/roles/{id:[0-9]+}', [
    'action' => 'UPDATE-ROLE',
    'uses' => 'RoleController@update',
]);

// Update
$api->put('/roles/{id:[0-9]+}/permissions', [
    'action' => 'UPDATE-ROLE-PERMISSIONS',
    'uses'   => 'RoleController@addPermission'
]);

$api->delete('/roles/{id:[0-9]+}', [
    'action' => 'DELETE-ROLE',
    'uses' => 'RoleController@delete',
]);
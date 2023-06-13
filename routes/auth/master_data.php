<?php
/**
 * Date: 2/22/2019
 * Time: 9:33 AM
 */
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

$api->get('/master-data', [
    'action' => 'VIEW-MASTER-DATA',
    'uses'   => 'MasterDataController@search',
]);

$api->get('/master-data/{id:[0-9]+}', [
    'action' => 'VIEW-MASTER-DATA',
    'uses'   => 'MasterDataController@view',
]);

$api->post('/master-data', [
    'action' => 'CREATE-MASTER-DATA',
    'uses'   => 'MasterDataController@create',
]);

$api->put('/master-data/{id:[0-9]+}', [
    'action' => 'UPDATE-MASTER-DATA',
    'uses'   => 'MasterDataController@update',
]);

$api->delete('/master-data/{id:[0-9]+}', [
    'action' => 'DELETE-MASTER-DATA',
    'uses'   => 'MasterDataController@delete',
]);
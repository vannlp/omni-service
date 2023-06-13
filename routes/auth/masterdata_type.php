<?php
/**
 * Created by PhpStorm.
 * Date: 2/23/2019
 * Time: 10:13 PM
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

$api->get('/masterdata-type', [
    'action' => 'VIEW-MASTER-DATA-TYPE',
    'uses'   => 'MasterDataTypeController@search',
]);

$api->get('/masterdata-type/{id:[0-9]+}', [
    'action' => 'VIEW-MASTER-DATA-TYPE',
    'uses'   => 'MasterDataTypeController@detail',
]);

$api->post('/masterdata-type', [
    'action' => 'CREATE-MASTER-DATA-TYPE',
    'uses'   => 'MasterDataTypeController@create',
]);

$api->put('/masterdata-type/{id:[0-9]+}', [
    'action' => 'UPDATE-MASTER-DATA-TYPE',
    'uses'   => 'MasterDataTypeController@update',
]);

$api->delete('/masterdata-type/{id:[0-9]+}', [
    'action' => 'DELETE-MASTER-DATA-TYPE',
    'uses'   => 'MasterDataTypeController@delete',
]);
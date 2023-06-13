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
$api->get('stores', [
    'action' => 'VIEW-STORE',
    'uses'   => 'StoreController@search',
]);

$api->get('stores/{id:[0-9]+}', [
    'action' => 'VIEW-STORE',
    'uses'   => 'StoreController@detail',
]);

$api->post('stores', [
    'action' => 'CREATE-STORE',
    'uses'   => 'StoreController@create',
]);

$api->put('stores/{id:[0-9]+}', [
    'action' => 'UPDATE-STORE',
    'uses'   => 'StoreController@update',
]);

$api->delete('stores/{id:[0-9]+}', [
    'action' => 'DELETE-STORE',
    'uses'   => 'StoreController@delete',
]);

////////////////////// MY STORE ///////////////////
$api->get('/stores/my-store', [
    //'action' => 'VIEW-MY-STORE',
    'uses' => 'StoreController@getMyStore',
]);

$api->get('/stores/my-store/{id:[0-9]+}', [
    //'action' => 'VIEW-MY-STORE',
    'uses' => 'StoreController@getMyStoreDetail',
]);

###################### USER STORE #################
$api->get('/stores/{id:[0-9]+}/users', [
    'action' => 'VIEW-USER',
    'uses'   => 'StoreController@listUsers',
]);
$api->post('/stores/{id:[0-9]+}/add-users', [
    'action' => 'UPDATE-STORE',
    'uses'   => 'StoreController@addUsers',
]);
$api->DELETE('/stores/{id:[0-9]+}/delete-users', [
    'action' => 'DELETE-STORE',
    'uses'   => 'StoreController@deleteUsers',
]);

##################### GET ALL STORE ################
$api->get('/stores-all', [
    'action' => 'VIEW-STORE-ALL',
    'uses'   => 'StoreController@listAllStore',
]);
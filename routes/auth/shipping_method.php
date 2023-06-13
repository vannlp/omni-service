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
$api->get('shipping-methods', [
    'action' => 'VIEW-SHIPPING-METHOD',
    'uses' => 'ShippingMethodController@search',
]);

$api->get('shipping-methods/{id:[0-9]+}', [
    'action' => 'VIEW-SHIPPING-METHOD',
    'uses' => 'ShippingMethodController@detail',
]);

$api->post('shipping-methods', [
    'action' => 'CREATE-SHIPPING-METHOD',
    'uses' => 'ShippingMethodController@create',
]);

$api->put('shipping-methods/{id:[0-9]+}', [
    'action' => 'UPDATE-SHIPPING-METHOD',
    'uses' => 'ShippingMethodController@update',
]);

$api->delete('shipping-methods/{id:[0-9]+}', [
    'action' => 'DELETE-SHIPPING-METHOD',
    'uses' => 'ShippingMethodController@delete',
]);

########################### NO AUTHENTICATION #####################
$api->get('/client/shipping-methods', [
    'name'   => 'SHIPPING-METHOD-VIEW-LIST',
    'action' => '',
    'uses'   => 'ShippingMethodController@getClientList',
]);
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

$api->get('/customers', [
    'action' => 'VIEW-CUSTOMER',
    'uses' => 'CustomerController@search',
]);

$api->get('/customers/{id:[0-9]+}', [
    'action' => 'VIEW-CUSTOMER',
    'uses' => 'CustomerController@view',
]);

$api->get('/customers/export', [
    'action' => 'VIEW-USER',
    'uses'   => 'CustomerController@exportCustomer',
]);

//$api->get('/customer-info/', [
//    'action' => 'VIEW-CUSTOMER-PROFILE',
//    'uses' => 'CustomerController@info',
//]);

$api->post('/customers', [
    'action' => 'UPDATE-CUSTOMER',
    'uses' => 'CustomerController@create',
]);

$api->post('/customers/{id:[0-9]+}/profile', [
    'action' => 'UPDATE-CUSTOMER',
    'uses' => 'CustomerController@updateProfile',
]);

$api->put('/customers/{id:[0-9]+}', [
    'action' => 'UPDATE-CUSTOMER',
    'uses' => 'CustomerController@update',
]);


$api->delete('/customers/{id:[0-9]+}', [
    'action' => 'DELETE-CUSTOMER',
    'uses' => 'CustomerController@delete',
]);

$api->put('/customers/{id:[0-9]+}/active', [
    'action' => 'UPDATE-CUSTOMER',
    'uses' => 'CustomerController@active',
]);

$api->put('/customers/add_for_seller', [
    'action' => 'UPDATE-CUSTOMER',
    'uses' => 'CustomerController@addCustomerForSeller',
]);

$api->get('/customers/{code}/card', [
    'action' => 'VIEW-CUSTOMER',
    'uses' => 'CustomerController@getCustomerCard',
]);
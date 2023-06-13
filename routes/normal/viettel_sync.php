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

$api->get('/viettel-dms-sync-data-demo', [
    'action' => '',
    'uses'   => 'SyncController@getDMSData',
]);

$api->get('/viettel-dms-sync-status-success', [
    'action' => '',
    'uses'   => 'SyncController@returnSuccess',
]);

$api->get('/viettel-dms-sync-status-fail', [
    'action' => '',
    'uses'   => 'SyncController@returnFail',
]);

$api->put('/order/change-status', [
    'action' => '',
    'uses'   => 'SyncController@updateStatus',
]);




//$api->post('/orders', [
//    'action' => '',
//    'uses'   => 'SyncController@createOrder',
//]);
//
//$api->put('/orders/{code:[0-9a-zA-Z_-]+}', [
//    'action' => '',
//    'uses'   => 'SyncController@updateOrder',
//]);
//
//$api->put('/orders/{code:[0-9a-zA-Z_-]+}/change-status', [
//    'action' => '',
//    'uses'   => 'SyncController@updateStatusOrder',
//]);
//
////$api->delete('/orders/{code:[0-9a-zA-Z_-]+}', [
////    'action' => '',
////    'uses'   => 'SyncController@deleteOrder',
////]);
//
//$api->post('/products', [
//    'action' => '',
//    'uses'   => 'SyncController@createProduct',
//]);
//
//$api->put('/products/{code:[0-9a-zA-Z_-]+}', [
//    'action' => '',
//    'uses'   => 'SyncController@updateProduct',
//]);
//
////$api->delete('/products/{code:[0-9a-zA-Z_-]+}', [
////    'action' => '',
////    'uses'   => 'SyncController@deleteOrder',
////]);
//
//$api->post('/distributors', [
//    'action' => '',
//    'uses'   => 'SyncController@createDistributor',
//]);
//
//$api->put('/distributors/{code:[0-9a-zA-Z_-]+}', [
//    'action' => '',
//    'uses'   => 'SyncController@updateDistributor',
//]);
//
////$api->delete('/distributors/{code:[0-9a-zA-Z_-]+}', [
////    'action' => '',
////    'uses'   => 'SyncController@deleteDistributor',
////]);
//
//$api->post('/customers', [
//    'action' => '',
//    'uses'   => 'SyncController@createCustomer',
//]);
//
//$api->put('/customers/{code:[0-9a-zA-Z_-]+}', [
//    'action' => '',
//    'uses'   => 'SyncController@updateCustomer',
//]);
//
////$api->delete('/customers/{code:[0-9a-zA-Z_-]+}', [
////    'action' => '',
////    'uses'   => 'SyncController@deleteCustomer',
////]);
//
//$api->post('/promotions', [
//    'action' => '',
//    'uses'   => 'SyncController@createPromotion',
//]);
//$api->put('/promotions/{code:[0-9a-zA-Z_-]+}', [
//    'action' => '',
//    'uses'   => 'SyncController@updatePromotion',
//]);
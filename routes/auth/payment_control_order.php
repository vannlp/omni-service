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

$api->get('/payment-control-orders', [
    'action' => 'VIEW-PAYMENT-CONTROL',
    'uses'   => 'PaymentControlOrderController@search',
]);

$api->get('/payment-control-orders/{id:[0-9]+}', [
    'action' => 'VIEW-PAYMENT-CONTROL',
    'uses'   => 'PaymentControlOrderController@detail',
]);

$api->post('/payment-control-orders', [
    'action' => 'CREATE-PAYMENT-CONTROL',
    'uses'   => 'PaymentControlOrderController@store',
]);

$api->put('/payment-control-orders/{id:[0-9]+}', [
    'action' => 'UPDATE-PAYMENT-CONTROL',
    'uses'   => 'PaymentControlOrderController@update',
]);

$api->delete('/payment-control-orders/{id:[0-9]+}', [
    'action' => 'DELETE-PAYMENT-CONTROL',
    'uses'   => 'PaymentControlOrderController@delete',
]);

$api->get('/payment-control-orders/reports/total-order', [
    'action' => 'VIEW-PAYMENT-CONTROL',
    'uses'   => 'PaymentControlOrderController@getOverview',
]);
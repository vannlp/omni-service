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

$api->get('order/{id}', [
    'action' => '',
    'uses' => 'OrderController@getClientOrder',
]);
$api->get('orderDms/{code}', [
    'action' => '',
    'uses' => 'OrderController@getOrderDms',
]);
$api->get('orderStatusDms/{code}', [
    'action' => '',
    'uses' => 'OrderController@getOrderStatusDms',
]);

$api->put('fake-order-online', [
    'action' => '',
    'uses' => 'ShippingOrderController@fakeChangeOrderToOnline',
]);

$api->post('fake-order-online-push-dms', [
    'action' => '',
    'uses' => 'ShippingOrderController@fakeGrabPushToDMS'
]);

$api->get('/jsonOrder/{code}', [
    'action' => '',
    'uses'   => 'OrderController@jsonOrderDms',
]);
$api->get('/jsonStatusOrder/{code}', [
    'action' => '',
    'uses'   => 'OrderController@jsonStatusOrderDms',
]);
$api->post('/repushOrderDMS/{code}', [
    'action' => '',
    'uses'   => 'OrderController@repushOrderToDMS',
]);
$api->post('/pushStatusOrderDMS/{code}', [
    'action' => '',
    'uses'   => 'OrderController@pushStatusOrderToDMS',
]);
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
$api->get('shipping-orders/list-shipping-type', [
    'action' => 'VIEW-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@getListShippingType',
]);

$api->get('shipping-orders', [
    'action' => 'VIEW-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@search',
]);

$api->get('shipping-orders/{shippingCode:[0-9a-zA-Z-_.]+}/detail', [
    'action' => 'VIEW-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@viewDetail',
]);

$api->get('shipping-orders/ship-fee', [
    'action' => 'VIEW-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@getShipFee',
]);


$api->get('shipping-orders/all-service', [
    'action' => 'VIEW-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@getAllService',
]);


$api->post('shipping-orders/{orderId}', [
    'action' => 'CREATE-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@postOrder',
]);

$api->get('shipping-orders/{shippingCode:[0-9a-zA-Z-_.]+}/status', [
    'action' => 'VIEW-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@getShippingOrderStatus',
]);

$api->put('shipping-orders/{id:[0-9]+}', [
    'action' => 'CREATE-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@updateShippingOrder',
]);

$api->put('shipping-orders/{shippingCode:[0-9a-zA-Z-_.]+}/cancel', [
    'action' => 'CREATE-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@cancelShippingOrder',
]);

$api->put('shipping-orders/push-order/{shippingCode:[0-9a-zA-Z-_.]+}', [
    'action' => 'CREATE-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@pushShippingOrderGrab',
]);

$api->get('/shipping_orders/print/{id:[0-9]+}', [
    'action' => 'PRINT-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@printShippingOrder',
]);

$api->get('/print-shipping-orders/{code}', [
    'action' => 'PRINT-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@printShippingReceiveAndPayment',
]);

$api->get('/print-export-order/{code}', [
    'name' => 'PRINT-SHIPPING-ORDER',
    'uses' => 'ShippingOrderController@printExportOrder',
]);


$api->get('/print-summary-by-selected-order', [
    'uses' => 'ShippingOrderController@reportSummaryBySelectedOrder',
]);

########################### NO AUTHENTICATION #####################
//$api->get('/client/shipping-orders', [
//    'name'   => 'SHIPPING-ORDER-VIEW-LIST',
//    'action' => '',
//    'uses'   => 'ShippingOrderController@getClientList',
//]);

$api->get('/client/shipping-orders/ship-fee', [
    'name' => 'SHIPPING-ORDER-VIEW-LIST',
    'uses' => 'ShippingOrderController@getClientShipFee',
]);
$api->get('/client/shipping-orders/all-ship-fee', [
    'name' => 'VIEW-SHIPPING-ORDER',
//    'action' => 'VIEW-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@getAllShipFee',
]);
$api->get('/client/shipping-orders/all-service', [
    'name' => 'VIEW-SHIPPING-ORDER',
//    'action' => 'VIEW-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@getAllService',
]);
$api->get('/client/shipping-orders/{shippingCode:[0-9a-zA-Z-_.]+}/view-detail-grab', [
    'name' => 'VIEW-SHIPPING-ORDER',
    //    'action' => 'VIEW-SHIPPING-ORDER',
    'uses'   => 'ShippingOrderController@getClientOrderDetailGrab',
]);

###########GHN###############
$api->get('/shipping-orders/{shippingCode:[0-9a-zA-Z-_.]+}/view-detail-ghn', [
    'name' => '',
    'uses' => 'ShippingOrderController@getOrderDetailGHN',
]);
$api->get('/shipping-orders/{shippingCode:[0-9a-zA-Z-_.]+}/return', [
    'name' => '',
    'uses' => 'ShippingOrderController@returnOrderGHN',
]);
$api->get('/shipping-orders/{shippingCode:[0-9a-zA-Z-_.]+}/view-detail-grab', [
    'name' => '',
    'uses' => 'ShippingOrderController@getOrderDetailGrab',
]);
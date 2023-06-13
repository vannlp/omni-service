<?php

$api->get('/order-status', [
    'action' => 'VIEW-ORDER-STATUS',
    'uses'   => 'OrderStatusController@search'
]);

$api->get('/order-status/{id:[0-9]+}', [
    'action' => 'VIEW-ORDER-STATUS',
    'uses'   => 'OrderStatusController@detail'
]);

$api->post('/order-status', [
    'action' => 'CREATE-ORDER-STATUS',
    'uses'   => 'OrderStatusController@create'
]);

$api->put('/order-status/{id:[0-9]+}', [
    'action' => 'UPDATE-ORDER-STATUS',
    'uses'   => 'OrderStatusController@update'
]);

$api->delete('/order-status/{id:[0-9]+}', [
    'action' => 'DELETE-ORDER-STATUS',
    'uses'   => 'OrderStatusController@delete'
]);
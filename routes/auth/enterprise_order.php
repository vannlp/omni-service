<?php
$api->get('/enterprise-orders', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'EnterpriseOrderController@search',
]);

$api->get('/enterprise-orders/{id:[0-9]+}', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'EnterpriseOrderController@detail',
]);

$api->put('/enterprise-orders/{id:[0-9]+}/update-status', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'EnterpriseOrderController@updateStatus',
]);

$api->delete('/enterprise-orders/{id:[0-9]+}', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'EnterpriseOrderController@updateStatus',
]);
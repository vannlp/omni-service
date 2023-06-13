<?php

$api->get('/ship_orders', [
    'action' => 'VIEW-SHIP-ORDER',
    'uses' => 'ShipOrderController@search',
]);

$api->get('/ship_orders/{id:[0-9]+}', [
    'action' => 'VIEW-SHIP-ORDER',
    'uses' => 'ShipOrderController@view',
]);

$api->post('/ship_orders', [
    'action' => 'CREATE-SHIP-ORDER',
'uses' => 'ShipOrderController@create',
]);

$api->put('/ship_orders/{id:[0-9]+}', [
    'action' => 'UPDATE-SHIP-ORDER',
    'uses' => 'ShipOrderController@update',
]);


$api->delete('/ship_orders/{id:[0-9]+}', [
    'action' => 'DELETE-SHIP-ORDER',
    'uses' => 'ShipOrderController@delete',
]);

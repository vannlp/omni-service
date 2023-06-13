<?php
$api->get('/inventories', [
    'action' => 'VIEW-INVENTORY',
    'uses'   => 'InventoryController@search',
]);

$api->get('/inventory/{id:[0-9]+}', [
    'action' => 'VIEW-INVENTORY',
    'uses'   => 'InventoryController@view',
]);

$api->get('/inventory/details', [
    'action' => 'VIEW-INVENTORY-DETAILS',
    'uses'   => 'InventoryController@searchDetails',
]);

$api->post('/inventory', [
    'action' => 'CREATE-INVENTORY',
    'uses'   => 'InventoryController@create',
]);

$api->put('/inventory/{id:[0-9]+}', [
    'action' => 'UPDATE-INVENTORY',
    'uses'   => 'InventoryController@update',
]);

$api->delete('/inventory/{id:[0-9]+}', [
    'action' => 'DELETE-INVENTORY',
    'uses'   => 'InventoryController@delete',
]);


$api->get('/inventory/export-list', [
    'action' => '',
    'uses'   => 'InventoryController@exportList',
]);

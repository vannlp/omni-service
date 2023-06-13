<?php

$api->get('/warehouse-details', [
    'action' => 'VIEW-WAREHOUSE-DETAIL',
    'uses'   => 'WarehouseDetailController@search',
]);

$api->get('/warehouse-details/{id:[0-9]+}', [
    'action' => 'VIEW-WAREHOUSE-DETAIL',
    'uses'   => 'WarehouseDetailController@view',
]);
$api->get('/warehouse-details-export', [
    'action' => '',
    'uses'   => 'WarehouseDetailController@exportwarehousedetails',
]);
<?php
/**
 * User: NGUYEN LE
 * Date: 01/01/2019
 * Time: 2:00 SA
 */

$api->get('/warehouses', [
    'action' => 'VIEW-WAREHOUSE',
    'uses' => 'WarehouseController@search',
]);

$api->get('/warehouses/{id:[0-9]+}', [
    'action' => 'VIEW-WAREHOUSE',
    'uses' => 'WarehouseController@detail',
]);

$api->post('/warehouses', [
    'action' => 'CREATE-WAREHOUSE',
    'uses' => 'WarehouseController@create',
]);

$api->put('/warehouses/{id:[0-9]+}', [
    'action' => 'UPDATE-WAREHOUSE',
    'uses' => 'WarehouseController@update',
]);

$api->delete('/warehouses/{id:[0-9]+}', [
    'action' => 'DELETE-WAREHOUSE',
    'uses' => 'WarehouseController@delete',
]);

$api->get('/warehouse-export-excel', [
    'action' => '',
    'uses'   => 'WarehouseController@warehouseExportExcel',
]);
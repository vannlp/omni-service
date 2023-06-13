<?php

$api->get('/channel-info', [
    'action' => '',
    'uses'   => 'DMSSyncController@funListChannel',
]);

$api->get('/channel-info/{id}', [
    'action' => '',
    'uses'   => 'DMSSyncController@funDetailChannel',
]);

//PRODUCT DMS IMPORTS

$api->get('/product-dms-imports', [
    'action' => '',
    'uses'   => 'DMSSyncController@listProductDMS',
]);

$api->post('/product-dms-imports', [
    'action' => '',
    'uses'   => 'DMSSyncController@createProductDMS',
]);

$api->get('/product-dms-imports/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@detailProductDMS',
]);

$api->put('/product-dms-imports/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@updateProductDMS',
]);

$api->delete('/product-dms-imports/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@deleteProductDMS',
]);
//END PRODUCT DMS IMPORTS


//PRODUCT INFO IMPORT

$api->get('/product-info', [
    'action' => '',
    'uses'   => 'DMSSyncController@listProductInfo',
]);

$api->post('/product-info', [
    'action' => '',
    'uses'   => 'DMSSyncController@createProductInfo',
]);

$api->get('/product-info/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@detailProductInfo',
]);

$api->put('/product-info/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@updateProductInfo',
]);


$api->delete('/product-info/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@deleteProductInfo',
]);

//END PRODUCT INFO IMPORT
$api->post('/channel-info', [
    'action' => '',
    'uses'   => 'DMSSyncController@funcPostChannel',
]);
$api->put('/channel-info/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@funcPutChannel',
]);
$api->delete('/channel-info/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@funDeleteChannel',
]);


$api->get('/warehouse-info', [
    'action' => '',
    'uses'   => 'DMSSyncController@funListWarehouse',
]);
$api->get('/warehouse-info/{id}', [
    'action' => '',
    'uses'   => 'DMSSyncController@funDetailWarehouse',
]);
$api->post('/warehouse-info', [
    'action' => '',
    'uses'   => 'DMSSyncController@funcPostWarehouse',
]);
$api->put('/warehouse-info/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@funcPutWarehouse',
]);
$api->delete('/warehouse-info/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@funDeleteWarehouse',
]);


$api->get('/warehousedetail-info', [
    'action' => '',
    'uses'   => 'DMSSyncController@funListWarehouseDetail',
]);
$api->get('/warehousedetail-info/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@funDetailWarehouseDetail',
]);
$api->post('/warehousedetail-info', [
    'action' => '',
    'uses'   => 'DMSSyncController@funcPostWarehouseDetail',
]);
$api->put('/warehousedetail-info/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@funcPutWarehouseDetail',
]);
$api->delete('/warehousedetail-info/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@funDeleteWarehouseDetail',
]);

$api->get('/customers', [
    'action' => '',
    'uses'   => 'DMSSyncController@searchCustomer',
]);

$api->post('/customer', [
    'action' => '',
    'uses'   => 'DMSSyncController@createCustomer',
]);

$api->get('/customer/{code:[0-9a-zA-Z_-]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@searchCustomer',
]);

$api->put('/customer/{code:[0-9a-zA-Z_-]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@updateCustomer',
]);

$api->delete('/customer/{code:[0-9a-zA-Z_-]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@deleteCustomer',
]);
$api->post('/routing', [
    'action' => '',
    'uses'   => 'DMSSyncController@createRouting',
]);

$api->put('/routing/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@updateRouting',
]);

$api->get('/routings', [
    'action' => '',
    'uses'   => 'DMSSyncController@listRouting',
]);

$api->delete('/routing/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@deleteRouting',
]);

$api->post('/routing-customer', [
    'action' => '',
    'uses'   => 'DMSSyncController@createCustomerRouting',
]);

$api->put('/routing-customer/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@updateCustomerRouting',
]);

$api->delete('/routing-customer/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@deleteRoutingCustomer',
]);

$api->get('/routing-customers', [
    'action' => '',
    'uses'   => 'DMSSyncController@listCustomerRouting',
]);

$api->post('/visit-plan', [
    'action' => '',
    'uses'   => 'DMSSyncController@createVisitPlan',
]);

$api->get('/visit-plans', [
    'action' => '',
    'uses'   => 'DMSSyncController@listVisitPlan',
]);

$api->put('/visit-plan/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@updateVisitPlan',
]);

$api->delete('/visit-plan/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@deleteVisitPlan',
]);

$api->get('/saleOrderConfigMin', [
    'action' => '',
    'uses'   => 'DMSSyncController@search',
]);

$api->get('/saleOrderConfigMin/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@detail',
]);
$api->post('/saleOrderConfigMin', [
    'action' => '',
    'uses'   => 'DMSSyncController@create',
]);
$api->put('/saleOrderConfigMin/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@update',
]);
$api->delete('/saleOrderConfigMin/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'DMSSyncController@delete',
]);
$api->put('/putOrder/{code}', [
    'action' => '',
    'uses'   => 'DMSSyncController@putOrder',
]);
$api->get('/getOrder/{code}', [
    'action' => '',
    'uses'   => 'DMSSyncController@getOrder',
]);
$api->get('/OrderDateFrom', [
    'action' => '',
    'uses'   => 'DMSSyncController@OrderDateFrom',
]);
$api->get('/OrderDateFromTo', [
    'action' => '',
    'uses'   => 'DMSSyncController@OrderDateFromTo',
]);
$api->get('/OrderByOrderNumber', [
    'action' => '',
    'uses'   => 'DMSSyncController@OrderByOrderNumber',
]);
// $api->post('/pushOrderDMS/{code}', [
//     'action' => '',
//     'uses'   => 'DMSSyncController@pushOrder',
// ]);

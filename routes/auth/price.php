<?php

$api->get('/prices', [
    'action' => 'VIEW-PRICE',
    'uses'   => 'PriceController@search',
]);

$api->get('/prices/{id:[0-9]+}', [
    'action' => 'VIEW-PRICE',
    'uses'   => 'PriceController@detail',
]);

$api->post('/prices', [
    'action' => 'CREATE-PRICE',
    'uses'   => 'PriceController@create',
]);

$api->put('/prices/{id:[0-9]+}', [
    'action' => 'UPDATE-PRICE',
    'uses'   => 'PriceController@update',
]);

$api->delete('/prices/{id:[0-9]+}', [
    'action' => 'DELETE-PRICE',
    'uses'   => 'PriceController@delete',
]);

##################### Price Details #######################

$api->get('/price/{id:[0-9]+}/details', [
    'action' => 'VIEW-PRICE-DETAIL',
    'uses'   => 'PriceController@viewPriceDetail'
]);

$api->get('/price/{id:[0-9]+}/detail', [
    'action' => 'VIEW-PRICE-DETAIL',
    'uses'   => 'PriceController@viewPriceDetails'
]);

$api->post('/price/{id:[0-9]+}/details', [
    'action' => 'CREATE-PRICE-DETAIL',
    'uses'   => 'PriceController@createPriceDetail'
]);

$api->post('/price/inheritance/{idPriceNew:[0-9]+}/{idPriceOld:[0-9]+}/details', [
    'action' => 'CREATE-PRICE-DETAIL',
    'uses'   => 'PriceController@createPriceDetailByCurrentPrice'
]);

$api->put('/price/{id:[0-9]+}/detail', [
    'action' => 'UPDATE-PRICE-DETAIL',
    'uses'   => 'PriceController@updatePriceDetails'
]);

$api->delete('/price/{id:[0-9]+}/detail', [
    'action' => 'DELETE-PRICE-DETAIL',
    'uses'   => 'PriceController@deleteDetail',
]);

$api->get('/price-export-excel', [
    'action' => '',
    'uses'   => 'PriceController@priceExportExcel',
]);
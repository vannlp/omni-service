<?php

$api->get('/product-hubs', [
    'action' => '',
    'uses'   => 'ProductHubController@searchDetail',
]);

$api->get('/product-hubs-export', [
    'action' => '',
    'uses'   => 'ProductHubController@exportProductHub',
]);

//$api->get('/product-hub/{id:[0-9]+}', [
//    'action' => '',
//    'uses'   => 'ProductHubController@detail',
//]);

$api->post('/product-hub', [
    'action' => '',
    'uses'   => 'ProductHubController@create',
]);

//$api->put('/product-hub/{id:[0-9]+}', [
//    'action' => '',
//    'uses'   => 'ProductHubController@update',
//]);

$api->delete('/product-hub/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'ProductHubController@delete',
]);

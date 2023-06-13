<?php

$api->get('/distributors', [
     'action' => '',
     'uses'   => 'DistributorController@search',
]);

$api->get('/distributor/check_order', [
     'action' => '',
     'uses'   => 'DistributorController@checkorder',
]);

$api->get('/distributor/{id:[0-9]+}', [
     'action' => '',
     'uses'   => 'DistributorController@detail',
]);

$api->put('/distributor/{id:[0-9]+}', [
     'action' => '',
     'uses'   => 'DistributorController@update',
]);
$api->post('/distributor', [
     'action' => '',
     'uses'   => 'DistributorController@create',
]);
$api->delete('/distributor/{id:[0-9]+}', [
     'action' => '',
     'uses'   => 'DistributorController@delete',
]);

$api->get('/distributors/export', [
     'action' => '',
     'uses'   => 'DistributorController@exportDistributors',
]);

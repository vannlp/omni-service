<?php
/*
 *
 */

$api->get('/region', [
     //'action' => 'VIEW-REGION',
     'uses' => 'RegionController@search',
]);

$api->get('/region/{id:[0-9]+}', [
     //'action' => 'VIEW-REGION',
     'uses' => 'RegionController@detail',
]);

$api->post('/region', [
     //'action' => 'CREATE-REGION',
     'uses' => 'RegionController@create',
]);

$api->put('/region/{id:[0-9]+}', [
     //'action' => 'UPDATE-REGION',
     'uses' => 'RegionController@update',
]);

$api->delete('/region/{id:[0-9]+}', [
     //'action' => 'DELETE-REGION',
     'uses' => 'RegionController@delete',
]);

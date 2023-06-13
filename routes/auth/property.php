<?php
/*
 *
 */

$api->get('/properties', [
    'uses' => 'PropertyController@search',
]);

$api->get('/property/{id:[0-9]+}', [
    'uses' => 'PropertyController@detail',
]);

$api->post('/property', [
    'uses' => 'PropertyController@create',
]);

$api->put('/property/{id:[0-9]+}', [
    'uses' => 'PropertyController@update',
]);

$api->delete('/property/{id:[0-9]+}', [
    'uses' => 'PropertyController@delete',
]);

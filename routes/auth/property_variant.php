<?php
/*
 *
 */

$api->get('/property-variants', [
    'uses' => 'PropertyVariantController@search',
]);

$api->get('/property-variant/{id:[0-9]+}', [
    'uses' => 'PropertyVariantController@detail',
]);

$api->post('/property-variant', [
    'uses' => 'PropertyVariantController@create',
]);

$api->put('/property-variant/{id:[0-9]+}', [
    'uses' => 'PropertyVariantController@update',
]);

$api->delete('/property-variant/{id:[0-9]+}', [
    'uses' => 'PropertyVariantController@delete',
]);

<?php

$api->get('/list-rent-ground', [
    'action' => '',
    'uses' => 'RentGroundController@search',
]);

$api->get('/rent-ground/{id}', [
    'action' => '',
    'uses' => 'RentGroundController@detail',
]);

$api->post('/create-rent-ground', [
    'action' => '',
    'uses' => 'RentGroundController@store',
]);
$api->put('/update-rent-ground/{id:[0-9]+}', [
    'action' => '',
    'uses' => 'RentGroundController@update',
]);
$api->delete('/delete-rent-ground/{id:[0-9]+}', [
    'action' => '',
    'uses' => 'RentGroundController@delete',
]);

$api->get('/rent-ground-export-excel', [
    'action' => '',
    'uses'   => 'RentGroundController@rentGroundExportExcel',
]);

//---------------------client----------------------
$api->post('/client/create-rent-ground', [
    'name'=> 'CREATE-RENT-GROUND',
    'action' => '',
    'uses' => 'RentGroundController@store',
]);

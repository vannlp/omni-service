<?php
$api->get('/rotation', [
    'action' => '',
    'uses'   => 'RotationController@search'
]);

$api->get('/rotation/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'RotationController@detail'
]);

$api->post('/rotation', [
    'action' => '',
    'uses'   => 'RotationController@create'
]);

$api->put('/rotation/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'RotationController@update'
]);

$api->delete('/rotation/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'RotationController@delete'
]);
$api->get('/rotation/random', [
    'action' => '',
    'uses'   => 'RotationController@random'
]);
// $api->get('/rotation/list', [
//     'action' => '',
//     'uses'   => 'RotationController@list'
// ]);
$api->get('/rotation/detail_rotation', [
    'action' => '',
    'uses'   => 'RotationController@rotationDetailUser'
]);
////CONDITION
$api->get('/rotation/condition', [
    'action' => '',
    'uses'   => 'RotationConditionController@search'
]);

$api->get('/rotation/condition/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'RotationConditionController@detail'
]);

$api->post('/rotation/condition', [
    'action' => '',
    'uses'   => 'RotationConditionController@create'
]);

$api->put('/rotation/condition/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'RotationConditionController@update'
]);

$api->delete('/rotation/condition/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'RotationConditionController@delete'
]);


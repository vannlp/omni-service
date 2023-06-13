<?php
$api->get('/cards', [
    'action' => 'VIEW-CARD',
    'uses'   => 'CardController@search',
]);

$api->get('/cards/{id:[0-9]+}', [
    'action' => 'VIEW-CARD',
    'uses'   => 'CardController@detail',
]);

$api->post('/cards', [
    'action' => 'CREATE-CARD',
    'uses'   => 'CardController@create',
]);

$api->put('/cards/{id:[0-9]+}', [
    'action' => 'UPDATE-CARD',
    'uses'   => 'CardController@update',
]);

$api->delete('/cards/{id:[0-9]+}', [
    'action' => 'DELETE-CARD',
    'uses'   => 'CardController@delete',
]);

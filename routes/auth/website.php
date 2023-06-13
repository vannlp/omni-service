<?php
$api->get('/websites', [
    'action' => 'VIEW-WEBSITE',
    'uses'   => 'WebsiteController@search',
]);

$api->get('/website/{id:[0-9]+}', [
    'action' => 'VIEW-WEBSITE',
    'uses'   => 'WebsiteController@detail',
]);

$api->post('/website', [
    'action' => 'CREATE-WEBSITE',
    'uses'   => 'WebsiteController@create',
]);

$api->put('/website/{id:[0-9]+}', [
    'action' => 'UPDATE-WEBSITE',
    'uses'   => 'WebsiteController@update',
]);

$api->delete('/website/{id:[0-9]+}', [
    'action' => 'DELETE-WEBSITE',
    'uses'   => 'WebsiteController@delete',
]);

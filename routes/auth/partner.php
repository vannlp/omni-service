<?php

$api->get('/list-partner-working-now', [
    'uses' => 'PartnerController@search',
]);

$api->get('nearest-partner/{id:[0-9]+}', [
    'uses' => 'PartnerController@view',
]);

$api->post('accept-order/{id:[0-9]+}', [
    'uses' => 'PartnerController@update',
]);

$api->get('/partners/recent', [
    'uses' => 'PartnerController@recent',
]);
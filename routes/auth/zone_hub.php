<?php
$api->get('/zone-hubs', [
    'action' => 'VIEW-ZONE-HUB',
    'uses' => 'ZoneHubController@search',
]);

$api->get('/zone-hub/{id:[0-9]+}', [
    'action' => 'VIEW-ZONE-HUB',
    'uses' => 'ZoneHubController@detail',
]);

$api->post('/zone-hub', [
    'action' => 'CREATE-ZONE-HUB',
    'uses' => 'ZoneHubController@create',
]);

$api->put('/zone-hub/{id:[0-9]+}', [
    'action' => 'UPDATE-ZONE-HUB',
    'uses' => 'ZoneHubController@update',
]);

$api->delete('/zone-hub/{store_id:[0-9]+}', [
    'action' => 'DELETE-ZONE-HUB',
    'uses'   => 'ZoneHubController@delete',
]);
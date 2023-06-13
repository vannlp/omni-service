<?php
$api->get('/active-omnichanel', [
    'action' => 'VIEW-STORE',
    'uses'   => 'ChanelController@search',
]);

$api->get('/active-omnichanel/{store_id:[0-9]+}', [
    'action' => 'VIEW-STORE',
    'uses'   => 'ChanelController@listActiveChanel',
]);

$api->put('/active-omnichanel/{store_id:[0-9]+}', [
    'action' => 'UPDATE-STORE',
    'uses'   => 'ChanelController@activeChanel',
]);

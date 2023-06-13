<?php
/**
 *
 */
$api->get('/notification-histories', [
    //'action' => 'VIEW-NOTIFICATION',
    'uses' => 'NotificationHistoryController@search',
]);

$api->get('/notification-histories/{id:[0-9]+}', [
    //'action' => 'VIEW-NOTIFICATION',
    'uses' => 'NotificationHistoryController@view',
]);

$api->put('/notification-histories/{id:[0-9]+}/read', [
    'action' => 'VIEW-NOTIFY',
    'uses'   => 'NotificationHistoryController@read',
]);
$api->put('/notification-histories/read', [
    'action' => 'VIEW-NOTIFY',
    'uses'   => 'NotificationHistoryController@readAll',
]);
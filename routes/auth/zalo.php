<?php

$api->get('zalo-chanel-store-info/{store_id:[0-9]+}', [
    'action' => 'VIEW-ZALO',
    'uses'   => 'ZaloController@show',
]);

$api->put('zalo-chanel-store-info/{store_id:[0-9]+}', [
    'action' => 'UPDATE-ZALO',
    'uses'   => 'ZaloController@createOrUpdate',
]);

$api->delete('zalo-chanel-store-info/{store_id:[0-9]+}', [
    'action' => 'DELETE-ZALO',
    'uses'   => 'ZaloController@delete',
]);

$api->put('sync-product-to-zalo/{store_id:[0-9]+}', [
    'action' => 'UPDATE-SYNC',
    'uses'   => 'ZaloSyncController@syncProduct',
]);

$api->get('sync-log', [
    'action' => 'VIEW-SYNC',
    'uses'   => 'ZaloSyncController@showLogs',
]);

$api->put('omnichanel/sync-zalo-order/{store_id:[0-9]+}', [
    'action' => '',
    'uses'   => 'ZaloSyncController@syncOrder',
]);

$api->put('omnichanel/sync-zalo-update-order/{order_id:[0-9]+}', [
    'action' => '',
    'uses'   => 'ZaloSyncController@syncUpdateOrder',
]);

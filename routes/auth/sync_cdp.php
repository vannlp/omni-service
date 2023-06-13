<?php
$api->get('/log-sync-cdp', [
    'action' => '',
    'uses'   => 'CdpController@logSyncCDP',
]);

$api->post('/repost-sync-cdp/{id}', [
    'action' => '',
    'uses'   => 'CdpController@respostSyncCDP',
]);

$api->post('/push-old-data-order-cdp', [
    'action' => '',
    'uses'   => 'CdpController@pushOldDataOrderCdp',
]);

$api->post('/push-old-data-customer-cdp', [
    'action' => '',
    'uses'   => 'CdpController@pushOldDataCustomerCdp',
]);

$api->post('/push-old-data-product-cdp', [
    'action' => '',
    'uses'   => 'CdpController@pushOldDataProductCdp',
]);

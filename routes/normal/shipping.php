<?php
$api->post('shipping/webhooks/update-status', [
    'uses' => 'ShippingOrderController@webhooksUpdateStatus'
]);

$api->post('shipping/webhooks/viettel-post', [
    'uses' => 'ShippingOrderController@webhooksViettelPost'
]);

$api->post('shipping/webhooks/vn-post', [
    'uses' => 'ShippingOrderController@webhooksVNPost'
]);

$api->post('shipping/webhooks/ghn', [
    'uses' => 'ShippingOrderController@webhooksGHNPost'
]);

$api->post('shipping/webhooks/ninja-van', [
    'uses' => 'ShippingOrderController@webhooksNinjaVan'
]);

$api->post('shipping/webhooks/grab', [
    'uses' => 'ShippingOrderController@webhooksGrabPost'
]);
$api->get('check-shipping/webhooks/grab', [
    'uses' => 'ShippingOrderController@checkGRAB'
]);
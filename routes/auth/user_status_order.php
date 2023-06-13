<?php
/**
 *
 */
$api->get('/status-order-histories', [
    //'action' => 'VIEW-ORDER-HISTORY',
    'uses'   => 'UserStatusOrderController@search',
]);

$api->put('/status-order-histories', [
    //'action' => 'UPDATE-ORDER-HISTORY',
    'uses'   => 'UserStatusOrderController@userStatusOrder',
]);
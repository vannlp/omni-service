<?php
$api->get('/client/stores/{code:[a-zA-Z0-9-_]+}', [
    'action' => '',
    'uses'   => 'StoreController@getStoreToken'
]);

$api->get('/client/store/products', [
    'action' => '',
    'uses'   => 'StoreController@getStoreProductToken'
]);
<?php

$api->get('/product_users', [
    'action' => 'VIEW-PRODUCT-USER',
    'uses'   => 'ProductUserController@search',
]);

$api->get('/product_users/{id:[0-9]+}', [
    'action' => 'VIEW-PRODUCT-USER',
    'uses'   => 'ProductUserController@detail',
]);

$api->post('/product_users', [
    'action' => 'CREATE-PRODUCT-USER',
    'uses'   => 'ProductUserController@create',
]);

$api->put('/product_users/{id:[0-9]+}', [
    'action' => 'UPDATE-PRODUCT-USER',
    'uses'   => 'ProductUserController@update',
]);


$api->delete('/product_users/{id:[0-9]+}', [
    'action' => 'DELETE-PRODUCT-USER',
    'uses'   => 'ProductUserController@delete',
]);

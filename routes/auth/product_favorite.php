<?php
$api->get('/product-favorites', [
//    'action' => 'VIEW-PRODUCT-FAVORITE',
    'uses'   => 'ProductFavoriteController@search',
]);

$api->get('/product-favorite/{id:[0-9]+}', [
//    'action' => 'VIEW-PRODUCT-FAVORITE',
    'uses'   => 'ProductFavoriteController@detail',
]);

$api->post('/product-favorite', [
//    'action' => 'CREATE-PRODUCT-FAVORITE',
    'uses'   => 'ProductFavoriteController@create',
]);

$api->put('/product-favorite/{id:[0-9]+}', [
//    'action' => 'UPDATE-PRODUCT-FAVORITE',
    'uses'   => 'ProductFavoriteController@update',
]);

$api->delete('/product-favorite/{id:[0-9]+}', [
//    'action' => 'DELETE-PRODUCT-FAVORITE',
    'uses'   => 'ProductFavoriteController@delete',
]);
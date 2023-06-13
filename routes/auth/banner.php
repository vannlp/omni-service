<?php

$api->get('/banners', [
    'action' => 'VIEW-BANNER',
    'uses'   => 'BannerController@search',
]);

$api->get('/banners/{id:[0-9]+}', [
    'action' => 'VIEW-BANNER',
    'uses'   => 'BannerController@detail',
]);

$api->get('/banners/{code}', [
    'action' => 'VIEW-BANNER',
    'uses'   => 'BannerController@view',
]);

$api->post('/banners', [
    'action' => 'CREATE-BANNER',
    'uses'   => 'BannerController@create',
]);

$api->put('/banners/{id:[0-9]+}', [
    'action' => 'UPDATE-BANNER',
    'uses'   => 'BannerController@update',
]);

$api->delete('/banners/{id:[0-9]+}', [
    'action' => 'DELETE-BANNER',
    'uses'   => 'BannerController@delete',
]);

$api->get('client/banners/{code}', [
    'name'   => 'BANNER-VIEW',
    'action' => '',
    'uses'   => 'BannerController@getClientBanner',
]);

$api->get('client/category-banners/{id:[0-9]+}', [
    'name'   => 'BANNER-VIEW',
    'action' => '',
    'uses'   => 'BannerController@getClientBannerCategory',
]);

<?php
/*
 *
 */

$api->get('/promotions', [
    //'action' => 'VIEW-PROMOTION',
    'uses' => 'PromotionController@search',
]);

$api->get('/promotions/{id:[0-9]+}', [
    //'action' => 'VIEW-PROMOTION',
    'uses' => 'PromotionController@view',
]);

$api->post('/promotions', [
    //'action' => 'CREATE-PROMOTION',
    'uses' => 'PromotionController@create',
]);

$api->put('/promotions/{id:[0-9]+}', [
    //'action' => 'UPDATE-PROMOTION',
    'uses' => 'PromotionController@update',
]);

$api->delete('/promotions/{id:[0-9]+}', [
    //'action' => 'DELETE-PROMOTION',
    'uses' => 'PromotionController@delete',
]);

$api->put('/promotions/{id:[0-9]+}/active', [
    //'action' => 'UPDATE-PROMOTION',
    'uses' => 'PromotionController@active',
]);

$api->get('/promotions/{id:[0-9]+}/users', [
    //'action' => 'UPDATE-PROMOTION',
    'uses' => 'PromotionController@listUserUsePromotion',
]);

////////////////////// MOBILE //////////////////////
$api->get('/promotions/my-promotions', [
    //'action' => 'VIEW-PROMOTION',
    'uses' => 'PromotionController@listMyPromotion',
]);
$api->get('/promotions/my-promotions/{code:[a-zA-Z0-9-_]+}', [
    //'action' => 'VIEW-PROMOTION',
    'uses' => 'PromotionController@viewMyPromotion',
]);

$api->get('/promotions/my-promotions/view/{id:[0-9]+}', [
    //'action' => 'VIEW-PROMOTION',
    'uses' => 'PromotionController@viewMyPromotionById',
]);
$api->get('/promotioncode', [
    //'action' => 'VIEW-PROMOTION',
    'uses' => 'PromocodeController@search',
]);
$api->post('/promotioncode', [
    //'action' => 'VIEW-PROMOTION',
    'uses' => 'PromocodeController@create',
]);
$api->put('/promotioncode/{id:[0-9]+}', [
    //'action' => 'VIEW-PROMOTION',
    'uses' => 'PromocodeController@update',
]);
$api->delete('/promotioncode/{id:[0-9]+}', [
    //'action' => 'VIEW-PROMOTION',
    'uses' => 'PromocodeController@delete',
]);
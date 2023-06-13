<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$api->get('/promotion-ads',
    [
        'action' => 'VIEW-PROMOTION-ADS',
        'uses'   => 'PromotionAdsController@search',
    ]
);

$api->post('/promotion-ads',
    [
        'action' => 'CREATE-PROMOTION-ADS',
        'uses'   => 'PromotionAdsController@create',
    ]
);

$api->put('/promotion-ads/{id:[0-9]+}',
    [
        'action' => 'UPDATE-PROMOTION-ADS',
        'uses'   => 'PromotionAdsController@update',
    ]
);

$api->delete('/promotion-ads/{id:[0-9]+}',
    [
        'action' => 'DELETE-PROMOTION-ADS', 
        'uses'   => 'PromotionAdsController@delete',
    ]
);

$api->get('/promotion-ads/{id:[0-9]+}',
    [
        'action' => 'VIEW-PROMOTION-ADS',
        'uses'   => 'PromotionAdsController@view',
    ]
);

####################### NOT AUTHENTICATION #######################
$api->get('/client/promotion-ads',
    [
        'name'   => 'VIEW-LIST-PROMOTION-ADS',
        'action' => '',
        'uses'   => 'PromotionAdsController@getClientPromotionAds'
    ]
);

$api->get('/client/promotion-ads/{id:[0-9]+}', 
    [
        'name'   => 'VIEW-DETAIL-PROMOTION-ADS',
        'action' => '',
        'uses'   => 'PromotionAdsController@getClientPromotionAdsDetail'
    ]);
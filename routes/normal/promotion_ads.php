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

$api->get('/promotion-adss',
    [
        'action' => '',
        'uses'   => 'PromotionAdsController@search',
    ]
);

$api->post('/promotion-ads',
    [
        'action' => '',
        'uses'   => 'PromotionAdsController@create',
    ]
);

$api->put('/promotion-ads/{id:[0-9]+}',
    [
        'action' => '',
        'uses'   => 'PromotionAdsController@update',
    ]
);

$api->delete('/promotion-ads/{id:[0-9]+}',
    [
        'action' => '',
        'uses'   => 'PromotionAdsController@delete',
    ]
);

$api->get('/promotion-ads/{id:[0-9]+}',
    [
        'action' => '',
        'uses'   => 'PromotionAdsController@view',
    ]
);
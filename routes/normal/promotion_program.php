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

$api->get('/promotion-programs',
    [
        'action' => '',
        'uses'   => 'PromotionProgramController@search',
    ]
);

$api->post('/promotion-program',
    [
        'action' => '',
        'uses'   => 'PromotionProgramController@create',
    ]
);

$api->put('/promotion-program/{id:[0-9]+}',
    [
        'action' => '',
        'uses'   => 'PromotionProgramController@update',
    ]
);

$api->delete('/promotion-program/{id:[0-9]+}',
    [
        'action' => '',
        'uses'   => 'PromotionProgramController@delete',
    ]
);

$api->get('/promotion-program/{id:[0-9]+}',
    [
        'action' => '',
        'uses'   => 'PromotionProgramController@view',
    ]
);
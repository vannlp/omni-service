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
        'action' => 'VIEW-PROMOTION-PROGRAM',
        'uses'   => 'PromotionProgramController@search',
    ]
);

$api->post('/promotion-program',
    [
        'action' => 'CREATE-PROMOTION-PROGRAM',
        'uses'   => 'PromotionProgramController@create',
    ]
);

$api->put('/promotion-program/{id:[0-9]+}',
    [
        'action' => 'UPDATE-PROMOTION-PROGRAM',
        'uses'   => 'PromotionProgramController@update',
    ]
);

$api->put('/promotion-program-status/{id:[0-9]+}',
    [
        'action' => 'UPDATE-PROMOTION-PROGRAM',
        'uses'   => 'PromotionProgramController@updateStatus',
    ]
);

$api->delete('/promotion-program/{id:[0-9]+}',
    [
        'action' => 'DELETE-PROMOTION-PROGRAM', 
        'uses'   => 'PromotionProgramController@delete',
    ]
);

$api->get('/promotion-program/{id:[0-9]+}',
    [
        'action' => 'VIEW-PROMOTION-PROGRAM',
        'uses'   => 'PromotionProgramController@view',
    ]
);

$api->get('/promotion-program-coming-soon',
    [
        'action' => 'VIEW-PROMOTION-PROGRAM',
        'uses'   => 'PromotionProgramController@getPromotionProgramComingSoon',
    ]
);

####################### NOT AUTHENTICATION #######################
$api->get('/client/promotion-program',
    [
        'name'   => 'VIEW-LIST-PROMOTION-PROGRAM',
        'action' => '',
        'uses'   => 'PromotionProgramController@getClientPromotionProgram'
    ]
);

$api->get('/client/promotion-program/{id:[0-9]+}', 
    [
        'name'   => 'VIEW-DETAIL-PROMOTION-PROGRAMS',
        'action' => '',
        'uses'   => 'PromotionProgramController@getClientPromotionProgramDetail'
    ]);
$api->get('/client/product-promotion-flash-sale',
    [
        'name'   => 'VIEW-LIST-PRODUCT-PROMOTION-PROGRAM-FLASH-SALE',
        'uses'   => 'PromotionController@productFlashSale'
    ]
);
$api->get('/client/product-promotion/{code:[a-zA-Z0-9-_]+}',
    [
        'name'   => 'VIEW-LIST-PRODUCT-PROMOTION-PROGRAM-FLASH-SALE',
        'uses'   => 'PromotionController@productPromotionDetail'
    ]
);
// Export
$api->get('/export/promotion-programs',
    [
        'name'   => 'PRINT-LST-PROMOTION',
        'uses'   => 'PromotionProgramController@exportPromotion'
    ]
);
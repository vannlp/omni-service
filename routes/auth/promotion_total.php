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

$api->get('/promotion-totals', [
    'action' => 'VIEW-PROMOTION-PROGRAM',
    'uses'   => 'PromotionTotalController@search',
]);

$api->get('/promotion-totals/{id:[0-9]+}', [
    'action' => 'VIEW-PROMOTION-PROGRAM',
    'uses'   => 'PromotionTotalController@view',
]);

$api->put('/promotion-totals/{id:[0-9]+}/approve', [
    'action' => 'UPDATE-PROMOTION-PROGRAM',
    'uses'   => 'PromotionTotalController@approve',
]);

$api->put('/promotion-totals/{id:[0-9]+}/reject', [
    'action' => 'UPDATE-PROMOTION-PROGRAM',
    'uses'   => 'PromotionTotalController@reject',
]);

///////////////////////// REPORT ////////////////////////
$api->get('/promotion-totals/report', [
    'action' => 'VIEW-PROMOTION-PROGRAM',
    'uses'   => 'PromotionTotalController@searchReport',
]);
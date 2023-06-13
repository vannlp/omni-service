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

$api->get('/access-trade', [
    'action' => 'VIEW-ACCESS-TRADE',
     'uses' => 'AccessTradeSettingController@search',
]);
//
$api->get('/access-trade/{id:[0-9]+}', [
    'action' => 'VIEW-ACCESS-TRADE',
'uses' => 'AccessTradeSettingController@detail',
]);
//
$api->post('/access-trade', [
    'action' => 'CREATE-ACCESS-TRADE',
'uses' => 'AccessTradeSettingController@create',
]);
//
$api->put('/access-trade/{id:[0-9]+}', [
    'action' => 'UPDATE-ACCESS-TRADE',
'uses' => 'AccessTradeSettingController@update',
]);
//
$api->delete('/access-trade/{id:[0-9]+}', [
    'action' => 'DELETE-ACCESS-TRADE',
'uses' => 'AccessTradeSettingController@delete',
]);
//
//########################### NO AUTHENTICATION #####################
$api->get('/client/access-trade-category/{id:[0-9]+}', [
    'name' => 'CLIENT-VIEW-ACCESS-TRADE',
    'uses' => 'AccessTradeSettingController@detailByCategory',
]);
<?php
/**
 * Created by PhpStorm.
 * User: SANG NGUYEN
 * Date: 2/24/2019
 * Time: 5:06 PM
 */
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

$api->get('/sale-prices', [
    'action' => 'VIEW-SALE-PRICE',
    'uses' => 'SalePriceController@search',
]);

$api->get('/sale-price/{id:[0-9]+}', [
    'action' => 'VIEW-SALE-PRICE',
    'uses' => 'SalePriceController@detail',
]);

$api->post('/sale-price', [
    'action' => 'CREATE-SALE-PRICE',
    'uses' => 'SalePriceController@create',
]);

$api->put('/sale-price/{id:[0-9]+}', [
    'action' => 'UPDATE-SALE-PRICE',
    'uses' => 'SalePriceController@update',
]);

$api->delete('/sale-price/{id:[0-9]+}', [
    'action' => 'DELETE-SALE-PRICE',
    'uses' => 'SalePriceController@delete',
]);
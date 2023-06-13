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
//
//$api->get('/categories', [
//    //'action' => 'VIEW-CATEGORY',
//    'uses' => 'CategoryController@search',
//]);
//
//$api->get('/categories/view/{id:[0-9]+}', [
//    //'action' => 'VIEW-CATEGORY',
//    'uses' => 'CategoryController@view',
//]);
//
//$api->get('/categories/{id:[0-9]+}', [
//    //'action' => 'VIEW-CATEGORY',
//    'uses' => 'CategoryController@detail',
//]);
//
//$api->get('/categories/hierarchy', [
//    'action' => 'VIEW-CATEGORY',
//    'uses'   => 'CategoryController@hierarchy',
//]);


$api->get('/client/product_categories', [
    'action' => '',
    'uses'   => 'CategoryController@getClientProductCategory',
]);

$api->get('/client/categories', [
    'action' => '',
    'uses'   => 'CategoryController@getClientCategory',
]);
$api->get('/client/categories/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'CategoryController@getClientCategoryDetail',
]);
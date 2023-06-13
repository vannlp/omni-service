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

$api->get('/categories', [
    'action' => 'VIEW-CATEGORY',
    'uses'   => 'CategoryController@search',
]);

$api->get('/categories/view/{id:[0-9]+}', [
    'action' => 'VIEW-CATEGORY',
    'uses'   => 'CategoryController@view',
]);

$api->get('/categories/{id:[0-9]+}', [
    'action' => 'VIEW-CATEGORY',
    'uses'   => 'CategoryController@detail',
]);

$api->get('/categories/hierarchy', [
    'action' => 'VIEW-CATEGORY',
    'uses'   => 'CategoryController@hierarchy',
]);

$api->post('/categories', [
    'action' => 'CREATE-CATEGORY',
    'uses'   => 'CategoryController@create',
]);

$api->put('/categories/{id:[0-9]+}', [
    'action' => 'UPDATE-CATEGORY',
    'uses'   => 'CategoryController@update',
]);


$api->delete('/categories/{id:[0-9]+}', [
    'action' => 'DELETE-CATEGORY',
    'uses'   => 'CategoryController@delete',
]);

$api->put('/categories/{id:[0-9]+}/active', [
    'action' => 'UPDATE-CATEGORY',
    'uses'   => 'CategoryController@active',
]);

############################################### NOT AUTHENTICATION #####################################
$api->get('/client/categories', [
    'name'   => 'CATEGORY-VIEW-LIST',
    'action' => '',
    'uses'   => 'CategoryController@getClientCategory',
]);
$api->get('/client/categories/{id:[0-9]+}', [
    'name'   => 'CATEGORY-VIEW-DETAIL',
    'action' => '',
    'uses'   => 'CategoryController@getClientCategoryDetail',
]);

$api->get('/client/categories-by-slug/{slug}', [
    'name'   => 'CATEGORY-VIEW-DETAIL',
    'action' => '',
    'uses'   => 'CategoryController@getClientCategoryDetailBySlug',
]);

$api->get('/client/categories-product-top-sale', [
    'name'   => 'CATEGORY-VIEW-LIST',
    'action' => '',
    'uses'   => 'CategoryController@getClientCategoryProductTopSale',
]);
//
$api->get('/client/categories/hierarchy', [
    'name'   => 'CATEGORY-VIEW-LIST',
    'action' => '',
    'uses'   => 'CategoryController@getClientHierarchy',
]);

$api->get('/client/all-categories', [
    'name'   => 'CATEGORY-VIEW-LIST',
    'action' => '',
    'uses'   => 'CategoryController@getClientAllCategory',
]);

$api->get('/client/detail-category/{id:[0-9]+}', [
    'name'   => 'CATEGORY-VIEW-LIST',
    'action' => '',
    'uses'   => 'CategoryController@getClientDetailCategory',
]);

// Category Export Excel
$api->get('/category-export-excel', [
    'action' => '',
    'uses'   => 'CategoryController@categoryExportExcel',
]);
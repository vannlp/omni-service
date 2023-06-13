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

$api->get('/catalog_options', [
    'action' => 'VIEW-CATALOG-OPTION',
    'uses'   => 'CatalogOptionController@search',
]);

$api->get('/catalog_options/{id:[0-9]+}', [
    'action' => 'VIEW-CATALOG-OPTION',
    'uses'   => 'CatalogOptionController@detail',
]);

$api->post('/catalog_options', [
    'action' => 'CREATE-CATALOG-OPTION',
    'uses'   => 'CatalogOptionController@store',
]);

$api->put('/catalog_options/{id:[0-9]+}', [
    'action' => 'UPDATE-CATALOG-OPTION',
    'uses'   => 'CatalogOptionController@update',
]);

$api->delete('/catalog_options/{id:[0-9]+}', [
    'action' => 'DELETE-CATALOG-OPTION',
    'uses'   => 'CatalogOptionController@delete',
]);
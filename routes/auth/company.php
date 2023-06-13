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

$api->get('/companies', [
    'action' => 'VIEW-COMPANY',
    'uses'   => 'CompanyController@search',
]);

$api->get('/companies/{id:[0-9]+}', [
    'action' => 'VIEW-COMPANY',
    'uses'   => 'CompanyController@detail',
]);

$api->post('/companies', [
    'action' => 'CREATE-COMPANY',
    'uses'   => 'CompanyController@store',
]);

$api->put('/companies/{id:[0-9]+}', [
    'action' => 'UPDATE-COMPANY',
    'uses'   => 'CompanyController@update',
]);

$api->delete('/companies/{id:[0-9]+}', [
    'action' => 'DELETE-COMPANY',
    'uses'   => 'CompanyController@delete',
]);

// Set Active Company
$api->put('/companies/{id:[0-9]+}/active', [
    'action' => 'VIEW-COMPANY',
    'uses'   => 'CompanyController@addPermission'
]);

////////////////////// MY COMPANY ///////////////////
$api->get('/companies/my-company', [
    'action' => 'VIEW-MY-COMPANY',
    'uses'   => 'CompanyController@getMyCompany',
]);

$api->get('/companies/my-company/{id:[0-9]+}', [
    'action' => 'VIEW-MY-COMPANY',
    'uses'   => 'CompanyController@viewDetailMyCompany',
]);
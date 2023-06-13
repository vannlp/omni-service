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
$api->get('features', [
    'action' => 'VIEW-FEATURE',
    'uses'   => 'FeatureController@search',
]);

$api->get('features/{id:[0-9]+}', [
    'action' => 'VIEW-FEATURE',
    'uses'   => 'FeatureController@detail',
]);
/*
$api->post('features', [
    'action' => 'CREATE-FEATURE',
    'uses'   => 'FeatureController@create',
]);

$api->put('features/{id:[0-9]+}', [
    //'action' => 'UPDATE-FEATURE',
    'uses'   => 'FeatureController@update',
]);

$api->delete('features/{id:[0-9]+}', [
    'action' => 'DELETE-FEATURE',
    'uses'   => 'FeatureController@delete',
]);
*/

$api->put('features/{id:[0-9]+}/activate', [
    'action' => 'UPDATE-FEATURE',
    'uses' => 'FeatureController@activate',
]);

$api->put('features/{id:[0-9]+}/in-activate', [
    'action' => 'UPDATE-FEATURE',
    'uses' => 'FeatureController@inActivate',
]);

$api->get('features/list-activated', [
    'action' => 'VIEW-FEATURE',
    'uses' => 'FeatureController@listActivated',
]);
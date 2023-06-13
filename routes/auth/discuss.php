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
$api->group(['prefix' => 'k-office'], function ($api) {
    $api->get('/discuss', [
        'action' => 'VIEW-DISCUSS',
        'uses'   => 'DiscussController@search',
    ]);

    $api->get('/issues/{issue_id:[0-9]+}/discuss', [
        'action' => 'VIEW-DISCUSS',
        'uses'   => 'DiscussController@detail',
    ]);

    $api->get('/discuss/{id:[0-9]+}', [
        'action' => 'VIEW-DISCUSS',
        'uses'   => 'DiscussController@detailDiscuss',
    ]);

    $api->get('/discuss/{id:[0-9]+}/count', [
        'action' => 'VIEW-DISCUSS',
        'uses'   => 'DiscussController@countLike',
    ]);

    $api->post('/discuss', [
        'action' => 'CREATE-DISCUSS',
        'uses'   => 'DiscussController@create',
    ]);

    $api->put('/discuss/{id:[0-9]+}', [
        'action' => 'UPDATE-DISCUSS',
        'uses'   => 'DiscussController@update',
    ]);

    $api->delete('/discuss/{id:[0-9]+}', [
        'action' => 'DELETE-DISCUSS',
        'uses'   => 'DiscussController@delete',
    ]);
});

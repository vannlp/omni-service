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
$api->group(['prefix' => "k-office"], function ($api) {
    $api->get('/issues', [
        'action' => 'VIEW-ISSUE',
        'uses'   => 'IssueController@search',
    ]);

    $api->get('/issueMine', [
        'action' => 'VIEW-ISSUE',
        'uses'   => 'IssueController@searchMine',
    ]);

    $api->get('/issue/{id:[0-9]+}', [
        'action' => 'VIEW-ISSUE',
        'uses'   => 'IssueController@detail',
    ]);

    $api->get('/issue-user', [
        'action' => 'VIEW-ISSUE',
        'uses'   => 'IssueController@issueUser',
    ]);

    $api->get('/issue-user/export', [
        'action' => 'VIEW-ISSUE',
        'uses'   => 'IssueController@exportIssueUser',
    ]);

    $api->post('/issue', [
        'action' => 'CREATE-ISSUE',
        'uses'   => 'IssueController@create',
    ]);

    $api->put('/issue/{id:[0-9]+}', [
        'action' => 'UPDATE-ISSUE',
        'uses'   => 'IssueController@update',
    ]);

    $api->delete('/issue/{id:[0-9]+}', [
    'action' => 'DELETE-ISSUE',
    'uses'   => 'IssueController@delete',
    ]);
});

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

// CULTURE
$api->get('/call-histories', [
    'action' => 'VIEW-CALL-HISTORY',
    'uses'   => 'CallHistoryController@search',
]);

$api->post('/call-history', [
    'action' => 'CREATE-CALL-HISTORY',
'uses' => 'CallHistoryController@create',
]);

$api->put('/call-history/{id:[0-9]+}', [
    'action' => 'UPDATE-CALL-HISTORY',
    'uses'   => 'CallHistoryController@update',
]);

$api->delete('/call-history/{id:[0-9]+}', [
    'action' => 'DELETE-CALL-HISTORY',
    'uses'   => 'CallHistoryController@delete',
]);

$api->get('/call-history/{id:[0-9]+}', [
    'action' => 'VIEW-CALL-HISTORY',
    'uses'   => 'CallHistoryController@detail',
]);

$api->put('/set-stop-call/{id:[0-9]+}', [
    'action' => 'UPDATE-CALL-HISTORY',
    'uses'   => 'CallHistoryController@setStopCall',
]);

$api->get('/call-history/report', [
    'action' => 'VIEW-CALL-HISTORY-REPORT',
    'uses'   => 'CallHistoryController@report',
]);

$api->post('/update-vote-consultant/{id:[0-9]+}', [
    'action' => 'VOTE-CALL',
    'uses'   => 'CallHistoryController@vote',
]);
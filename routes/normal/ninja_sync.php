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

$api->get('/ninja_article_interaction', [
    'action' => '',
    'uses'   => 'NinjaSyncController@search',
]);
//CREATE
$api->post('/ninja_article_interaction', [
    'action' => '',
    'uses'   => 'NinjaSyncController@articleInteraction',
]);

$api->post('/ninja_list_group', [
    'action' => '',
    'uses'   => 'NinjaSyncController@listGroup',
]);

$api->post('/ninja_list_member_group', [
    'action' => '',
    'uses'   => 'NinjaSyncController@listMemberGroup',
]);

$api->post('/ninja_list_live', [
    'action' => '',
    'uses'   => 'NinjaSyncController@listLive',
]);

$api->post('/ninja_uid_friend', [
    'action' => '',
    'uses'   => 'NinjaSyncController@uidFriend',
]);

$api->post('/ninja_uid_analysis', [
    'action' => '',
    'uses'   => 'NinjaSyncController@createUidAnalysis',
]);

$api->post('/ninja_filter_post', [
    'action' => '',
    'uses'   => 'NinjaSyncController@createFilterPost',
]);

$api->post('/ninja_uid_post', [
    'action' => '',
    'uses'   => 'NinjaSyncController@createUidPost',
]);

$api->post('/ninja_list_user', [
    'action' => '',
    'uses'   => 'NinjaSyncController@createListUser',
]);

$api->post('/ninja_filter_interactive', [
    'action' => '',
    'uses'   => 'NinjaSyncController@createFilterInteractive',
]);

$api->post('/ninja_list_comment', [
    'action' => '',
    'uses'   => 'NinjaSyncController@createListComment',
]);

$api->post('/ninja_list_page_id', [
    'action' => '',
    'uses'   => 'NinjaSyncController@createListPageId',
]);
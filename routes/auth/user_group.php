<?php
/**
 * User: Administrator
 * Date: 01/01/2019
 * Time: 10:31 PM
 */

$api->get('/user_groups', [
    'action' => 'VIEW-CUSTOMER-GROUP',
    'uses'   => 'UserGroupController@search',
]);

$api->get('/user_groups/{id:[0-9]+}', [
    'action' => 'VIEW-CUSTOMER-GROUP',
    'uses'   => 'UserGroupController@detail',
]);

$api->post('/user_groups', [
    'action' => 'UPDATE-CUSTOMER-GROUP',
    'uses'   => 'UserGroupController@create',
]);

$api->put('/user_groups/{id:[0-9]+}', [
    'action' => 'UPDATE-CUSTOMER-GROUP',
    'uses'   => 'UserGroupController@update',
]);

$api->delete('/user_groups/{id:[0-9]+}', [
    'action' => 'UPDATE-CUSTOMER-GROUP',
    'uses'   => 'UserGroupController@delete',
]);

$api->get('/user-groups-export-excel', [
    'action' => '',
    'uses'   => 'UserGroupController@userGroupExportExcel',
]);
$api->get('/client/user_groups', [
    'action' => '',
    'name'   => 'GET-CLIENT-USER-GROUP',
    'uses'   => 'UserGroupController@searchClient',
]);
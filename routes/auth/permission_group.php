<?php
/**
 * Date: 1/11/2019
 * Time: 12:40 AM
 */

$api->get('/permission_groups', [
    'action' => 'VIEW-PERMISSION-GROUP',
    'uses'   => 'PermissionGroupController@search',
]);

$api->get('/permission_groups/{id:[0-9]+}', [
    'action' => 'VIEW-PERMISSION-GROUP',
    'uses'   => 'PermissionGroupController@detail',
]);

$api->post('/permission_groups', [
    'action' => 'CREATE-PERMISSION-GROUP',
    'uses'   => 'PermissionGroupController@create',
]);

$api->put('/permission_groups/{id:[0-9]+}', [
    'action' => 'UPDATE-PERMISSION-GROUP',
    'uses'   => 'PermissionGroupController@update',
]);

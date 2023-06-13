<?php
/**
 * User: Administrator
 * Date: 01/01/2019
 * Time: 10:31 PM
 */

$api->get('/customer_groups', [
    'action' => 'VIEW-CUSTOMER-GROUP',
    'uses'   => 'CustomerGroupController@search',
]);

$api->get('/customer_groups/{id:[0-9]+}', [
    'action' => 'VIEW-CUSTOMER-GROUP',
    'uses'   => 'CustomerGroupController@detail',
]);

$api->post('/customer_groups', [
    'action' => 'UPDATE-CUSTOMER-GROUP',
    'uses'   => 'CustomerGroupController@create',
]);

$api->put('/customer_groups/{id:[0-9]+}', [
    'action' => 'UPDATE-CUSTOMER-GROUP',
    'uses'   => 'CustomerGroupController@update',
]);

$api->delete('/customer_groups/{id:[0-9]+}', [
    'action' => 'UPDATE-CUSTOMER-GROUP',
    'uses'   => 'CustomerGroupController@delete',
]);
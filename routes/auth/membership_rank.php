<?php
/**
 * User: Administrator
 * Date: 01/01/2019
 * Time: 10:31 PM
 */

$api->get('/membership-ranks', [
    'action' => 'VIEW-MEMBERSHIP-RANK',
    'uses'   => 'MembershipRankController@search',
]);

$api->get('/membership-ranks/{id:[0-9]+}', [
    'action' => 'VIEW-MEMBERSHIP-RANK',
    'uses'   => 'MembershipRankController@detail',
]);

$api->post('/membership-ranks', [
    'action' => 'CREATE-MEMBERSHIP-RANK',
    'uses'   => 'MembershipRankController@create',
]);

$api->put('/membership-ranks/{id:[0-9]+}', [
    'action' => 'UPDATE-MEMBERSHIP-RANK',
    'uses'   => 'MembershipRankController@update',
]);

$api->delete('/membership-ranks/{id:[0-9]+}', [
    'action' => 'DELETE-MEMBERSHIP-RANK',
    'uses'   => 'MembershipRankController@delete',
]);

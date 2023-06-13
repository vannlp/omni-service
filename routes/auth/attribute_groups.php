<?php

$api->group(['prefix' => 'attribute-groups'], function ($api) {

    $api->get('', [
        'action' => 'VIEW-ATTRIBUTE',
        'uses'   => 'AttributeGroupController@search'
    ]);

    $api->get('types', [
        'action' => 'VIEW-ATTRIBUTE',
        'uses'   => 'AttributeGroupController@getType'
    ]);

    $api->get('{id}/show', [
        'action' => 'VIEW-ATTRIBUTE',
        'uses'   => 'AttributeGroupController@show'
    ]);

    $api->post('', [
        'action' => 'CREATE-ATTRIBUTE',
        'uses'   => 'AttributeGroupController@store'
    ]);

    $api->put('{id}', [
        'action' => 'UPDATE-ATTRIBUTE',
        'uses'   => 'AttributeGroupController@update'
    ]);

    $api->delete('{id}', [
        'action' => 'DELETE-ATTRIBUTE',
        'uses'   => 'AttributeGroupController@delete'
    ]);
});

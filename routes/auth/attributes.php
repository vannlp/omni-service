<?php

$api->group(['prefix' => 'attributes'], function ($api) {

    $api->get('', [
        'action' => 'VIEW-ATTRIBUTE',
        'uses'   => 'AttributeController@search'
    ]);

    $api->get('{id}/show', [
        'action' => 'VIEW-ATTRIBUTE',
        'uses'   => 'AttributeController@show'
    ]);

    $api->post('', [
        'action' => 'CREATE-ATTRIBUTE',
        'uses'   => 'AttributeController@store'
    ]);

    $api->put('{id}', [
        'action' => 'UPDATE-ATTRIBUTE',
        'uses'   => 'AttributeController@update'
    ]);

    $api->delete('{id}', [
        'action' => 'DELETE-ATTRIBUTE',
        'uses'   => 'AttributeController@delete'
    ]);
});

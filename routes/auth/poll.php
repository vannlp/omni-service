<?php

$api->group(['prefix' => 'polls'], function ($api) {
    $api->get('/', [
            'action' => '',
            'uses'   => 'PollController@getAll'
    ]);

    $api->get('/{poll_id:[0-9]+}', [
            'action' => '',
            'uses'   => 'PollController@show'
    ]);

    $api->get('/by-code/{code}', [
            'action' => '',
            'uses'   => 'PollController@showByCode'
    ]);

    $api->post('/', [
            'action' => '',
            'uses'   => 'PollController@store'
    ]);

    $api->put('/{poll_id:[0-9]+}', [
            'action' => '',
            'uses'   => 'PollController@update',
    ]);

    $api->delete('/{poll_id:[0-9]+}', [
            'action' => '',
            'uses'   => 'PollController@delete',
    ]);

    $api->group(['prefix' => 'perform'], function ($api) {
        $api->get('/{poll_id:[0-9]+}', [
                'action' => '',
                'uses'   => 'PollController@showPerform',
        ]);

        $api->get('/by-code/{code}', [
                'action' => '',
                'uses'   => 'PollController@showPerformByCode',
        ]);

        $api->post('/{poll_id:[0-9]+}', [
                'action' => '',
                'uses'   => 'PollController@perform',
        ]);

        $api->post('/perform-by-code/{code}', [
                'action' => '',
                'uses'   => 'PollController@performByCode',
        ]);

        $api->get('/{poll_id:[0-9]+}/{perform_id:[0-9]+}', [
                'action' => '',
                'uses'   => 'PollController@showPerformDetail',
        ]);
    });
});

<?php
$api->group(['prefix' => "k-office"], function ($api) {
    $api->get('/modules', [
        'action' => 'VIEW-MODULE',
        'uses'   => 'IssueModuleController@search',
    ]);

    $api->get('/module/{id:[0-9]+}', [
        'action' => 'VIEW-MODULE',
        'uses'   => 'IssueModuleController@detail',
    ]);

    $api->post('/module', [
        'action' => 'CREATE-MODULE',
        'uses'   => 'IssueModuleController@create',
    ]);

    $api->put('/module/{id:[0-9]+}', [
        'action' => 'UPDATE-MODULE',
        'uses'   => 'IssueModuleController@update',
    ]);

    $api->delete('/module/{id:[0-9]+}', [
        'action' => 'DELETE-MODULE',
        'uses'   => 'IssueModuleController@delete',
    ]);
});

<?php
$api->group(['prefix' => "k-office"], function ($api) {
    $api->get('/module-categories', [
        'action' => 'VIEW-CATEGORY-ISSUE',
        'uses'   => 'IssueModuleCategoryController@search',
    ]);

    $api->get('/module-category/{id:[0-9]+}', [
        'action' => 'VIEW-CATEGORY-ISSUE',
        'uses'   => 'IssueModuleCategoryController@detail',
    ]);

    $api->post('/module-category', [
        'action' => 'CREATE-CATEGORY-ISSUE',
        'uses'   => 'IssueModuleCategoryController@create',
    ]);

    $api->put('/module-category/{id:[0-9]+}', [
        'action' => 'UPDATE-CATEGORY-ISSUE',
        'uses'   => 'IssueModuleCategoryController@update',
    ]);

    $api->delete('/module-category/{id:[0-9]+}', [
        'action' => 'DELETE-CATEGORY-ISSUE',
        'uses'   => 'IssueModuleCategoryController@delete',
    ]);
});

<?php

$api->group(['prefix' => 'post'], function ($api) {
    $api->get('/list', [
        'action' => 'VIEW-POST',
        'uses'   => 'PostController@searchPost'
    ]);
    $api->get('/detail/{id:[0-9]+}', [
        'action' => 'VIEW-POST',
        'uses'   => 'PostController@detailPost'
    ]);
    $api->post('/create', [
        'action' => 'CREATE-POST',
        'uses'   => 'PostController@createPost'
    ]);
    $api->put('/update/{id:[0-9]+}', [
        'action' => 'UPDATE-POST',
        'uses'   => 'PostController@updatePost'
    ]);
    $api->delete('/delete/{id:[0-9]+}', [
        'action' => 'DELETE-POST',
        'uses'   => 'PostController@deletePost'
    ]);
});

$api->group(['prefix' => 'category'], function ($api) {
    $api->get('/list', [
        'action' => 'VIEW-POST-CATEGORY',
        'uses'   => 'PostController@searchCategory'
    ]);
    $api->get('/detail/{id:[0-9]+}', [
        'action' => 'VIEW-POST-CATEGORY',
        'uses'   => 'PostController@detailCategory'
    ]);
    $api->post('/create', [
        'action' => 'CREATE-POST-CATEGORY',
        'uses'   => 'PostController@createCategory'
    ]);
    $api->put('/update/{id:[0-9]+}', [
        'action' => 'UPDATE-POST-CATEGORY',
        'uses'   => 'PostController@updateCategory'
    ]);
    $api->delete('/delete/{id:[0-9]+}', [
        'action' => 'DELETE-POST-CATEGORY',
        'uses'   => 'PostController@deleteCategory'
    ]);
});

// ============== Client ================

$api->group(['prefix' => 'client'], function ($api) {
    $api->group(['prefix' => 'category'], function ($api) {
        $api->get('/list', [
            'name' => 'VIEW-POST-CATEGORY',
            'uses'   => 'PostController@clientGetListCategory'
        ]);
        $api->get('/detail/{id:[0-9]+}', [
            'name' => 'VIEW-POST-CATEGORY',
            'uses'   => 'PostController@clientGetDetailCategory'
        ]);
    });

    $api->group(['prefix' => 'post'], function ($api) {
        $api->get('/list', [
            'name' => 'VIEW-POST',
            'uses'   => 'PostController@clientGetListPost'
        ]);
        $api->get('/detail/{id:[0-9]+}', [
            'name' => 'VIEW-POST',
            'uses'   => 'PostController@clientGetDetailPost'
        ]);
    });
});

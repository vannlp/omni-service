<?php

$api->group(['prefix' => 'client'], function ($api) {
    $api->group(['prefix' => 'nndd'], function ($api) {
        $api->get('list-products', [
            'uses' => 'NNDD\\NNDDController@listProduct',
            'name' => 'NNDD-USE'
        ]);
        $api->get('detail-product/{slug}', [
            'uses' => 'NNDD\\NNDDController@detaiProductBySlug',
            'name' => 'NNDD-USE'
        ]);
    });
});

$api->group(['prefix' => 'nndd'], function ($api) {
   
});


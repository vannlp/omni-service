<?php

$api->group(['prefix' => 'product-variant'], function ($api) {

    $api->get('{productId}', [
        'action' => '',
        'uses'   => 'ProductVariantController@listProductVariantByProductId'
    ]);

    $api->get('combination-attributes/{productId}', [
        'action' => '',
        'uses'   => 'ProductVariantController@combinationAttribute'
    ]);

    $api->put('{productId}', [
        'action' => '',
        'uses'   => 'ProductVariantController@update'
    ]);

    $api->delete('promotion/{id}', [
        'action' => '',
        'uses'   => 'ProductVariantController@deletePromotion'
    ]);


    $api->delete('{id}', [
        'action' => '',
        'uses'   => 'ProductVariantController@delete'
    ]);
});

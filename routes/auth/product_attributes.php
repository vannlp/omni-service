<?php

$api->group(['prefix' => 'product-attributes'], function ($api) {

    $api->get('{productId}', [
        'action' => 'UPDATE-PRODUCT',
        'uses'   => 'ProductAttributeController@getListByProductId'
    ]);

    $api->put('{productId}', [
        'action' => 'UPDATE-PRODUCT',
        'uses'   => 'ProductAttributeController@update'
    ]);

    $api->delete('{id}', [
        'action' => 'DELETE-PRODUCT',
        'uses'   => 'ProductAttributeController@delete'
    ]);
});

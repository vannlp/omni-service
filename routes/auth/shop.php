<?php

$api->group(['prefix' => 'shop', 'namespace' => 'Shop', 'name' => 'SHOP'], function ($api) {
    $api->post('order', 'OrderController@store');
    $api->get('products', 'ProductController@getListProduct');
    $api->get('product-detail/{id}', 'ProductController@getProductDetail');
    $api->get('product-detail-by-slug/{slug}', 'ProductController@getProductDetailBySlug');
});
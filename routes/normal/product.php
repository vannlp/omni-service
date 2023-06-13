<?php
$api->get('/client/products', [
    'uses' => 'ProductController@getClientProduct'
]);

$api->get('/client/products-data-string', [
    'uses' => 'ProductController@getClientProductDataString'
]);

$api->get('/client/product-detail/{id:[0-9]+}', [
    'uses' => 'ProductController@getClientProductDetail'
]);

$api->get('/client/related-product/{id:[0-9]+}', [
    'uses' => 'ProductController@getClientRelatedProduct'
]);

$api->get('/client/product/{id:[0-9]+}/comments', [
    'uses' => 'ProductController@clientGetCommentByProduct'
]);

$api->get('/client/product/{id:[0-9]+}/questions', [
    'uses' => 'ProductController@clientGetQuestionByProduct'
]);

################################ Product Rate ###############################
$api->get('/get-star-rate/{id:[0-9]+}', [
    'uses' => 'ProductController@getStarRate',
]);
###############################Product School#######################
$api->get('/products', [
    'uses' => 'ProductController@getProduct'
]);

$api->get('/product-detail/{id:[0-9]+}', [
    'uses' => 'ProductController@getProductDetail'
]);

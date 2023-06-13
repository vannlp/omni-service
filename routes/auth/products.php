<?php

$api->get('/products', [
    'action' => 'VIEW-PRODUCT',
    'uses'   => 'ProductController@search',
]);

$api->get('/product-lite', [
    'action' => 'VIEW-PRODUCT',
    'uses'   => 'ProductController@searchLite',
]);

$api->get('/products/{id:[0-9]+}', [
    'action' => 'VIEW-PRODUCT',
    'uses'   => 'ProductController@detail',
]);

$api->post('/products', [
    'action' => 'CREATE-PRODUCT',
    'uses'   => 'ProductController@create',
]);

$api->put('/products/{id:[0-9]+}', [
    'action' => 'UPDATE-PRODUCT',
    'uses'   => 'ProductController@update',
]);

$api->put('/products/updateSortOrder', [
    'action' => 'UPDATE-PRODUCT',
    'uses'   => 'ProductController@updateSortOrder',
]);


$api->delete('/products/{id:[0-9]+}', [
    'action' => 'DELETE-PRODUCT',
    'uses'   => 'ProductController@delete',
]);

$api->get('/products/top-search', [
    'action' => 'VIEW-PRODUCT',
    'uses'   => 'ProductController@topProductSearch',
]);

$api->get('/products/top-keyword-search', [
    'action' => '',
    'uses'   => 'ProductController@topKeywordSearch',
]);

$api->get('/client/products/top-keyword-search', [
    'name' => 'PRODUCT-VIEW',
    'uses' => 'ProductController@topKeywordSearch',
]);

$api->get('/products/search-keyword-search/{keyword}', [
    'action' => '',
    'uses'   => 'ProductController@searchKeyword',
]);

//export Product
$api->get('/export-products', [
    'action' => '',
    'uses'   => 'ProductController@exportProduct',
]);
//Product Review
$api->post('/product-review', [
    'action' => '',
    'uses'   => 'ProductController@productReview',
]);
$api->get('/product-review/{id:[0-9]+}/total-point', [
    'action' => '',
    'uses'   => 'ProductController@getTotalPointReview',
]);
$api->get('/product-review/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'ProductController@getProductReview',
]);

$api->get('/bestsellers', [
    'action' => '',
    'uses'   => 'ProductController@bestsellers',
]);

$api->get('/admin-get-products', [
    'action' => '',
    'uses' => 'ProductController@get_product_admin'
]);

########################### NO AUTHENTICATION #####################

$api->get('/client/products', [
    'name'   => 'PRODUCT-VIEW-LIST',
    'action' => '',
    'uses'   => 'ProductController@getClientProduct'
]);

$api->get('/client/products-data-string', [
    'name'   => 'PRODUCT-VIEW-LIST',
    'action' => '',
    'uses'   => 'ProductController@getClientProductDataString'
]);

$api->get('/client/product/sale-off', [
    'name'   => 'PRODUCT-VIEW-LIST',
    'action' => '',
    'uses'   => 'ProductController@getClientProductSaleOff'
]);

$api->get('/client/product-detail/{id:[0-9]+}', [
    'name'   => 'PRODUCT-VIEW-DETAIL',
    'action' => '',
    'uses'   => 'ProductController@getClientProductDetail'
]);

$api->get('/client/related-product/{id:[0-9]+}', [
    'name'   => 'PRODUCT-VIEW-RELATED',
    'action' => '',
    'uses'   => 'ProductController@getClientRelatedProduct'
]);

$api->get('/client/advance-related-product/{id:[0-9]+}', [
    'name'   => 'PRODUCT-VIEW-RELATED',
    'action' => '',
    'uses'   => 'ProductController@getClientRelatedProductAdvance'
]);

$api->get('/client/product-by-category', [
    'name'   => 'PRODUCT-VIEW-BY-CATEGORY',
    'action' => '',
    'uses'   => 'ProductController@getClientProductByCategory'
]);

$api->get('/client/products/top-search', [
    'name' => 'PRODUCT-VIEW-LIST',
    'uses' => 'ProductController@getClientTopProductSearch',
]);

$api->get('/client/product-detail-by-slug/{slug:[a-zA-Z0-9-_]+}', [
    'name'   => 'PRODUCT-VIEW-DETAIL',
    'action' => '',
    'uses'   => 'ProductController@getClientProductDetailBySlug'
]);

$api->get('/client/products/top-sale', [
    'name' => 'PRODUCT-VIEW-LIST',
    'uses' => 'ProductController@getClientTopProductSale',
]);

// Product Export Excel
$api->get('/product-export-excel', [
    'action' => '',
    'uses'   => 'ProductController@productExportExcel',
]);

$api->get('/client/product/{productId:[0-9]+}/comments', [
    'name' => 'PRODUCT-VIEW',
    'uses' => 'ProductController@clientGetCommentByProduct'
]);

$api->get('/client/get-star-rate/{id:[0-9]+}', [
    'name' => 'PRODUCT-VIEW',
    'uses' => 'ProductController@getStarRate',
]);
$api->get('/client/key-filter-product', [
    'name' => 'FILTER-PRODUCT',
    'uses'   => 'ProductController@keyWordSearchProduct',
]);
$api->put('/client/product-filter', [
    'name' => 'FILTER-PRODUCT',
    'uses'   => 'ProductController@productFilter',
]);
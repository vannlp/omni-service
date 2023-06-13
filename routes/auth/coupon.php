<?php
$api->get('/coupons', [
    'action' => 'VIEW-COUPON',
    'uses'   => 'CouponController@search'
]);

$api->get('/coupon/{id:[0-9]+}', [
    'action' => 'VIEW-COUPON',
    'uses'   => 'CouponController@detail'
]);

$api->get('/coupon-code/{id:[0-9]+}', [
    'action' => 'VIEW-COUPON',
    'uses'   => 'CouponController@GetListCouponCode'
]);
$api->put('/coupon-code-delete-all', [
    // 'action' => 'DELETE-ALL-COUPON',
    'uses'   => 'CouponController@deleteAllCouponById'
]);
$api->get('/coupon-code/search/{id:[0-9]+}', [
    // 'action' => 'SEARCH-COUPON-DETAIL',
    'uses'   => 'CouponController@searchCouponCodeDetail'
]);

$api->get('/coupon/histories', [
    'action' => 'VIEW-COUPON',
    'uses'   => 'CouponController@couponHistory'
]);
$api->get('/coupon/histories/{code}', [
    'action' => 'VIEW-COUPON',
    'uses'   => 'CouponController@couponHistoryDetail'
]);


$api->post('/coupon', [
    'action' => 'CREATE-COUPON',
    'uses'   => 'CouponController@create'
]);


$api->delete('/coupon-code/{id:[0-9]+}', [
    'action' => 'DELETE-COUPON',
    'uses'   => 'CouponController@deletecodeCoupon'
]);

$api->put('/coupon/{id:[0-9]+}', [
    'action' => 'EDIT-COUPON',
    'uses'   => 'CouponController@update'
]);

$api->put('/coupon-code/{id:[0-9]+}', [
    // 'action' => 'EDIT-COUPON',
    'uses'   => 'CouponController@updateCouponCode'
]);

$api->put('/coupon-status/{id:[0-9]+}',[
        'action' => 'EDIT-COUPON',
        'uses'   => 'CouponController@updateStatus',
]);

$api->delete('/coupon/{id:[0-9]+}', [
    'action' => 'DELETE-COUPON',
    'uses'   => 'CouponController@delete'
]);

$api->get('/admin-get-coupons', [
    // 'name' => 'CLIENT-VIEW-COUPON',
    'uses' => 'CouponController@adminGetCoupon'
]);

//Client
$api->get('/client/coupons', [
    'name' => 'CLIENT-VIEW-COUPON',
    'uses' => 'CouponController@clientGetCoupon'
]);

$api->get('/client/coupon/{id:[0-9]+}', [
    'name' => 'CLIENT-VIEW-COUPON',
    'uses' => 'CouponController@clientGetCouponDetail'
]);
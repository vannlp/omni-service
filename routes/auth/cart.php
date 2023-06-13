<?php

$api->get('/cart', [
    'action' => 'VIEW-CART',
    'uses'   => 'CartController@detail'
]);

$api->post('/add-to-cart', [
    'action' => 'ADD-TO-CART',
    'uses'   => 'CartController@addToCart'
]);

$api->put('/cart', [
    'action' => 'UPDATE-CART',
    'uses'   => 'CartController@update'
]);

$api->put('/update-product-in-cart/{id:[0-9]+}', [
    'action' => 'UPDATE-PRODUCT-IN-CART',
    'uses'   => 'CartController@updateProductInCart'
]);

$api->put('/update-note-product-in-cart/{id:[0-9]+}', [
    'action' => 'UPDATE-PRODUCT-IN-CART',
    'uses'   => 'CartController@updateNoteProductInCart'
]);


$api->delete('/cart', [
    'action' => 'DELETE-CART',
    'uses'   => 'CartController@delete'
]);

$api->delete('/remove-product-in-cart/{id:[0-9]+}', [
    'action' => 'REMOVE-PRODUCT-IN-CART',
    'uses'   => 'CartController@removeProductInCart'
]);

$api->put('/cart/add-coupon', [
    'action' => 'ADD-COUPON-IN-CART',
    'uses'   => 'CartController@addCoupon'
]);

$api->put('/admin-add-coupon', [
    'name' => 'ADD-COUPON-IN-CART-CLIENT',
    'uses' => 'CartController@adminAddCoupon'
]);

$api->put('/cart/remove-coupon', [
    'action' => 'ADD-COUPON-IN-CART',
    'uses'   => 'CartController@removeCoupon'
]);

$api->put('/set-payment-method', [
    'name' => 'UPDATE-CART',
    'uses'   => 'CartController@clientSetPaymentMethod'
]);

$api->post('/admin-new-cart', [
    'action' => '',
    'uses' => 'CartController@create_cart_admin'
]);

$api->put('/admin-update-cart-info', [
    'action' => '',
    'uses' => 'CartController@update_cart_admin'
]);

$api->get('/admin-get-cart', [
    'action' => '',
    'uses' => 'CartController@get_cart_admin'
]);

$api->post('/admin-add-to-cart', [
    'action' => '',
    'uses' => 'CartController@admin_add_to_cart'
]);

$api->put('/admin-update-qty-product/{id}', [
    'action' => '',
    'uses' => 'CartController@update_qty_cart_admin'
]);

$api->delete('/admin-remove-product-in-cart/{id:[0-9]+}', [
    'action' => '',
    'uses' => 'CartController@removeAdminProductInCart'
]);

// Client Cart
$api->put('/client/set-shipping-method', [
    'name' => 'UPDATE-SHIPPING-METHOD',
    'uses'   => 'CartController@setShippingMethod'
]);

$api->get('/client/cart', [
    'name' => 'CLIENT-VIEW-CART',
    'uses' => 'CartController@detail'
]);

$api->get('/client/customer-info/{phone}', [
    'name' => 'GET-CUSTOMER-INFO',
    'uses' => 'CartController@getCustomerInfo'
]);

$api->post('/client/add-to-cart', [
    'name' => 'CLIENT-ADD-TO-CART',
    'uses' => 'CartController@addToCart'
]);

$api->delete('/client/cart', [
    'name' => 'CLIENT-DELETE-CART',
    'uses' => 'CartController@delete'
]);

$api->put('/client/update-product-in-cart/{id:[0-9]+}', [
    'name' => 'UPDATE-PRODUCT-IN-CART',
    'uses' => 'CartController@updateClientProductInCart'
]);

$api->put('/client/update-note-product-in-cart/{id:[0-9]+}', [
    'name' => 'UPDATE-PRODUCT-IN-CART',
    'uses' => 'CartController@updateClientNoteProductInCart'
]);

$api->put('/client/cart/add-coupon', [
    'name' => 'ADD-COUPON-IN-CART-CLIENT',
    'uses' => 'CartController@clientAddCoupon'
]);

$api->put('/client/cart/remove-coupon', [
    'name' => 'REMOVE-COUPON-IN-CART-CLIENT',
    'uses'   => 'CartController@removeCoupon'
]);

$api->put('/client/cart/remove-coupon-delivery', [
    'name' => 'REMOVE-COUPON-DELIVERY-IN-CART-CLIENT',
    'uses' => 'CartController@removeCouponDelivery'
]);

$api->put('/client/cart/add-promocode', [
    'name' => 'ADD-PROMOCODE-IN-CART-CLIENT',
    'uses'   => 'CartController@addCoupon'
]);

$api->put('/client/cart/remove-promocode', [
    'name' => 'REMOVE-PROMOCODE-IN-CART-CLIENT',
    'uses'   => 'CartController@removePromocode'
]);

$api->put('/client/cart/add-voucher', [
    'name' => 'ADD-VOUCHER-IN-CART-CLIENT',
    'uses'   => 'CartController@addCoupon'
]);

$api->put('/client/cart/remove-voucher', [
    'name' => 'REMOVE-VOUCHER-IN-CART-CLIENT',
    'uses'   => 'CartController@removeVoucher'
]);

$api->delete('/client/remove-product-in-cart/{id:[0-9]+}', [
    'name' => 'REMOVE-PRODUCT-IN-CART',
    'uses' => 'CartController@removeClientProductInCart'
]);
$api->put('/client/remove-product-in-cart', [
    'name' => 'REMOVE-PRODUCT-IN-CART',
    'uses' => 'CartController@removeClientProductInCartIds'
]);
$api->delete('/client/remove-cart-detail/{session}', [
    'name' => 'REMOVE-PRODUCT-IN-CART',
    'uses' => 'CartController@clientDelete'
]);

$api->put('/client/update-cart-info', [
    'name' => 'UPDATE-PRODUCT-IN-CART',
    'uses' => 'CartController@clientUpdateCartInfo'
]);
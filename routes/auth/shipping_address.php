<?php

$api->get('/shipping_address', [
    'action' => 'VIEW-SHIPPING-ADDRESS',
    'uses'   => 'ShippingAddressController@search',
]);

$api->get('/shipping_address/{id:[0-9]+}', [
    'action' => 'VIEW-SHIPPING-ADDRESS',
    'uses'   => 'ShippingAddressController@detail',
]);

$api->post('/shipping_address', [
    'action' => 'CREATE-SHIPPING-ADDRESS',
    'uses'   => 'ShippingAddressController@create',
]);

$api->put('/shipping_address/{id:[0-9]+}', [
    'action' => 'UPDATE-SHIPPING-ADDRESS',
    'uses'   => 'ShippingAddressController@update',
]);

$api->delete('/shipping_address/{id:[0-9]+}', [
    'action' => 'DELETE-SHIPPING-ADDRESS',
    'uses'   => 'ShippingAddressController@delete',
]);

$api->put('/shipping_address/set_default/{id:[0-9]+}', [
    'action' => 'UPDATE-SHIPPING-ADDRESS',
    'uses'   => 'ShippingAddressController@setIsDefault',
]);

$api->put('/set-shipping-address/{id:[0-9]+}', [
    'action' => 'UPDATE-SHIPPING-ADDRESS',
    'uses'   => 'ShippingAddressController@setShippingAddressCart',
]);

$api->put('client/set-shipping-address', [
    'name' => 'CLIENT-UPDATE-SHIPPING-ADDRESS',
    'uses'   => 'ShippingAddressController@setShippingAddressCart',
]);
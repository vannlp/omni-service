<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

########################### CONFIG SHPPING #####################

$api->post('/config-shipping', [
    'uses'   => 'ConfigShippingController@create',
]);

$api->get('/config-shipping/{id}', [
    'uses' => 'ConfigShippingController@detail',
]);

$api->put('/config-shipping/{id}', [
    'uses' => 'ConfigShippingController@update',
]);
// cập nhật trạng thái 
$api->put('/config-shipping/{id:[0-9]+}/active', [
    //'action' => 'UPDATE-PROMOTION',
    'uses' => 'ConfigShippingController@update_status',
]);


// Xóa
$api->delete('/config-shipping/{id:[0-9]+}/delete', [
    //'action' => 'UPDATE-PROMOTION',
    'uses' => 'ConfigShippingController@delete',
]);

$api->get('/config-shipping', [
    'uses' => 'ConfigShippingController@search',
]);

$api->get('/get-config-shipping', [
    'uses' => 'ConfigShippingController@listClient'
]);

$api->get('/client/get-config-shipping', [
    'uses' => 'ConfigShippingController@listClient'
]);

$api->put('/client/set-config-shipping', [
    'uses' => 'ConfigShippingController@setShippingMethod'
]);

?>



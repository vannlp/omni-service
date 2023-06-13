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
$api->get('user/wallet', [
    'action' => '',
    'uses'   => 'WalletController@view',
]);

$api->get('user/wallets', [
    'action' => 'VIEW_WALLET_LIST',
    'uses'   => 'WalletController@search',
]);

$api->get('user/wallets/{id:[0-9]+}', [
    'action' => 'VIEW_WALLET_LIST',
    'uses'   => 'WalletController@detail',
]);

$api->post('user/wallet', [
    'action' => '',
    'uses'   => 'WalletController@create',
]);
<?php

$api->group(['prefix' => 'brand'], function ($api) {

    $api->get('', [
        'action' => 'VIEW-BRAND',
        'uses'   => 'BrandController@search'
    ]);

    $api->get('{id}/show', [
        'action' => 'VIEW-BRAND',
        'uses'   => 'BrandController@show'
    ]);

    $api->post('', [
        'action' => 'CREATE-BRAND',
        'uses'   => 'BrandController@store'
    ]);

    $api->put('{id}', [
        'action' => 'UPDATE-BRAND',
        'uses'   => 'BrandController@update'
    ]);

    $api->delete('{id}', [
        'action' => 'DELETE-BRAND',
        'uses'   => 'BrandController@delete'
    ]);
});

$api->group(['prefix' => 'client'], function ($api) {
    $api->get('brands', [
        'name' => 'VIEW-LIST-BRAND',
        'uses' => 'BrandController@getClientBrand'
    ]);
});

$api->get('/brand-export-excel', [
    'action' => '',
    'uses'   => 'BrandController@brandExportExcel',
]);
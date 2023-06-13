<?php

$api->group(['prefix' => 'brand'], function ($api) {

    $api->get('', ['uses'   => 'BrandController@search']);
});

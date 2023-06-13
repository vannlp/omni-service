<?php

$api->get('/collections', [
    'action' => 'VIEW-COLLECTION',
    'uses'   => 'CollectionController@search',
]);

$api->get('/collections/{collectionId}', [
    'action' => 'VIEW-COLLECTION',
    'uses'   => 'CollectionController@show',
]);

$api->post('/collections', [
    'action' => 'CREATE-COLLECTION',
    'uses'   => 'CollectionController@create',
]);

$api->put('/collections/{collectionId}', [
    'action' => 'UPDATE-COLLECTION',
    'uses'   => 'CollectionController@update',
]);

$api->put('/collections/{collectionId}/assign-products', [
    'action' => 'CREATE-COLLECTION',
    'uses'   => 'CollectionController@assignProducts',
]);
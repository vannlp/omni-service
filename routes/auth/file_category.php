<?php
$api->get('/file-category', [
    'action' => 'VIEW-FILE',
    'uses'   => 'FileCategoryController@search',
]);

$api->get('/file-category/{id:[0-9]+}', [
    'action' => 'VIEW-FILE',
    'uses'   => 'FileCategoryController@detail',
]);

$api->post('/file-category', [
    'action' => 'UPLOAD-FILE',
    'uses'   => 'FileCategoryController@store',
]);

$api->put('/file-category/{id:[0-9]+}', [
    'action' => 'UPLOAD-FILE',
    'uses'   => 'FileCategoryController@update',
]);

$api->delete('/file-category/{id:[0-9]+}', [
    'action' => 'UPLOAD-FILE',
    'uses'   => 'FileCategoryController@delete',
]);

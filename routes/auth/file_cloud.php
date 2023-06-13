<?php

$api->get('/files-cloud', [
    'action' => 'UPLOAD-FILE',
    'uses'   => 'FileCloudController@search',
]);

$api->post('/files-cloud', [
    'action' => 'UPLOAD-FILE',
    'uses'   => 'FileCloudController@store',
]);

$api->put('/files-cloud/{id:[0-9]+}', [
    'action' => 'UPLOAD-FILE',
    'uses'   => 'FileCloudController@update',
]);

$api->delete('/files-cloud/{id:[0-9]+}', [
    'action' => 'UPLOAD-FILE',
    'uses'   => 'FileCloudController@delete',
]);

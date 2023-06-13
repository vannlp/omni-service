<?php

$api->get('/files', [
    'action' => 'VIEW-FILE',
    'uses'   => 'FileController@search',
]);


$api->get('/files/{id:[0-9]+}', [
    'action' => 'VIEW-FILE',
    'uses'   => 'FileController@detail',
]);

$api->get('/files/download', [
    'action' => 'DOWNLOAD-FILE',
    'uses'   => 'FileController@download',
]);

$api->post('/files/upload', [
    'action' => 'UPLOAD-FILE',
    'uses'   => 'FileController@upload',
]);

$api->post('/files', [
    'action' => 'CREATE-FILE',
    'uses'   => 'FileController@create',
]);

$api->put('/files/{id:[0-9]+}', [
    'action' => 'UPDATE-FILE',
    'uses'   => 'FileController@update',
]);

$api->put('/files/{id:[0-9]+}/moveFile', [
    'action' => 'UPDATE-FILE',
    'uses'   => 'FileController@moveFile',
]);

$api->delete('/files/{id:[0-9]+}', [
    'action' => 'DELETE-FILE',
    'uses'   => 'FileController@delete',
]);

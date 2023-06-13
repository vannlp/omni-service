<?php
$api->get('/folders', [
    'action' => 'VIEW-FOLDER',
    'uses'   => 'FolderController@search',
]);

$api->get('/folders/{id:[0-9]+}', [
    'action' => 'VIEW-FOLDER',
    'uses'   => 'FolderController@detail',
]);

$api->post('/folders', [
    'action' => 'CREATE-FOLDER',
    'uses'   => 'FolderController@create',
]);

$api->put('/folders/{id:[0-9]+}', [
    'action' => 'UPDATE-FOLDER',
    'uses'   => 'FolderController@update',
]);

$api->put('/folders/{id:[0-9]+}/moveFolder', [
    'action' => 'UPDATE-FOLDER',
    'uses'   => 'FolderController@moveFolder',
]);

$api->delete('/folders/{id:[0-9]+}', [
    'action' => 'DELETE-FOLDER',
    'uses'   => 'FolderController@delete',
]);
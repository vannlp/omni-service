<?php

$api->get('/module', [
    'action' => 'UPLOAD-FILE',
    'uses'   => 'ModuleController@search',
]);

$api->post('/module', [
    'action' => 'UPLOAD-FILE',
    'uses'   => 'ModuleController@store',
]);

$api->put('/module/{id:[0-9]+}', [
    'action' => 'UPLOAD-FILE',
    'uses'   => 'ModuleController@update',
]);

$api->delete('/module/{id:[0-9]+}', [
    'action' => 'UPLOAD-FILE',
    'uses'   => 'ModuleController@delete',
]);

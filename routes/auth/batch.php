<?php
$api->get('/batches', [
    'action' => 'VIEW-BATCH',
    'uses' => 'BatchController@search',
]);

$api->get('/batch/{id:[0-9]+}', [
    'action' => 'VIEW-BATCH',
    'uses' => 'BatchController@detail',
]);

$api->post('/batch', [
    'action' => 'CREATE-BATCH',
    'uses' => 'BatchController@create',
]);

$api->put('/batch/{id:[0-9]+}', [
    'action' => 'UPDATE-BATCH',
    'uses' => 'BatchController@update',
]);

$api->delete('/batch/{id:[0-9]+}', [
    'action' => 'DELETE-BATCH',
    'uses'   => 'BatchController@delete',
]);

$api->get('/batch-export-excel', [
    'action' => '',
    'uses'   => 'BatchController@batchExportExcel',
]);
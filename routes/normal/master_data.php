<?php
$api->get('/master-data', [
    'action' => '',
    'uses'   => 'MasterDataController@searchNotLogin',
]);

$api->get('/master-data/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'MasterDataController@view',
]);
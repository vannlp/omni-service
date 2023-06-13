<?php
$api->get('/partners', [
    'uses'   => 'PartnerNutifoodController@search', 
]); 

$api->get('/partner/{id:[0-9]+}', [
    'uses'   => 'PartnerNutifoodController@detail', 
]); 

$api->put('/partner/{id:[0-9]+}', [
    'uses'   => 'PartnerNutifoodController@update', 
]); 

$api->delete('/partner/{id:[0-9]+}', [
    'uses'   => 'PartnerNutifoodController@delete', 
]); 

$api->get('/partners/export-excel', [
    'action' => '',
    'uses'   => 'PartnerNutifoodController@partnerExportExcel',
]);

$api->post('/partner', [
    'uses'   => 'PartnerNutifoodController@create', 
]); 

//---------------------client----------------------
$api->post('/client/partner', [
    'uses'   => 'PartnerNutifoodController@create', 
    'name'=> 'CREATE-PARTNER',
]); 

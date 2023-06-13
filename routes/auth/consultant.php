<?php
$api->get('/consultants', [
//    'action' => 'VIEW-CONSULTANT',
    'uses'   => 'ConsultantController@search'
]);

$api->get('/consultant/{id:[0-9]+}', [
//    'action' => 'VIEW-CONSULTANT',
    'uses'   => 'ConsultantController@detail'
]);

$api->post('/consultant', [
//    'action' => 'CREATE-CONSULTANT',
    'uses'   => 'ConsultantController@create'
]);

$api->put('/consultant/{id:[0-9]+}', [
//    'action' => 'UPDATE-CONSULTANT',
    'uses'   => 'ConsultantController@update'
]);

$api->delete('/consultant/{id:[0-9]+}', [
//    'action' => 'DELETE-CONSULTANT',
    'uses'   => 'ConsultantController@delete',
]);

$api->put('/set-online-consultant', [
//    'action' => 'UPDATE-CONSULTANT',
    'uses'   => 'ConsultantController@setOnlineConsultant',
]);

$api->get('/active-consultants', [
//    'action' => 'VIEW-CONSULTANT',
    'uses'   => 'ConsultantController@activeConsultants',
]);

$api->put('/update-consultants', [
//    'action' => 'UPDATE-CONSULTANT',
    'uses'   => 'ConsultantController@updateConsultant',
]);

$api->get('/get-consultant-status', [
//    'action' => 'VIEW-CONSULTANT',
    'uses'   => 'ConsultantController@getConsultantStatus',
]);
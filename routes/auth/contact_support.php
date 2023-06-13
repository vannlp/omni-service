<?php
$api->get('/contact-supports', [
    'action' => 'VIEW-CONTACT',
    'uses' => 'ContactSupportController@search',
]);

$api->get('/contact-supports/{id:[0-9]+}', [
    'action' => 'VIEW-CONTACT',
    'uses' => 'ContactSupportController@detail',
]);

$api->post('/contact-supports', [
    'action' => 'CREATE-CONTACT',
    'uses' => 'ContactSupportController@create',
]);

$api->put('/contact-supports/{id:[0-9]+}', [
    'action' => 'UPDATE-CONTACT',
    'uses' => 'ContactSupportController@update',
]);

$api->delete('/contact-supports/{id:[0-9]+}', [
    'action' => 'DELETE-CONTACT',
    'uses' => 'ContactSupportController@delete',
]);

$api->post('client/contacts', [
    'name' => 'CREATE-CONTACT',
    'uses' => 'ContactSupportController@createContact',
]);

$api->post('client/exportform', [
    'name' => 'CREATE-CONTACT',

    'uses' => 'ContactSupportController@exportForm',
]);
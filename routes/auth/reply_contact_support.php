<?php

$api->get('/reply-contact-supports', [
    'action' => '',
    'uses'   => 'ReplyContactSupportController@search',
]);

$api->get('/reply-contact-supports/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'ReplyContactSupportController@detail',
]);

$api->get('/reply-contact-supports/reply/{contactId:[0-9]+}', [
    'action' => '',
    'uses'   => 'ReplyContactSupportController@view',
]);

$api->post('/reply-contact-supports', [
    'action' => '',
    'uses'   => 'ReplyContactSupportController@create',
]);
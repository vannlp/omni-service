<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$api->get(
    '/feedback',
    [
        'action' => 'VIEW-FEEDBACK',
        'uses'   => 'FeedbackController@search',
    ]
);

$api->post(
    '/feedback',
    [
        'action' => 'CREATE-FEEDBACK',
        'uses'   => 'FeedbackController@create',
    ]
);

$api->put(
    '/feedback/{id:[0-9]+}',
    [
        'action' => 'UPDATE-FEEDBACK',
        'uses'   => 'FeedbackController@update',
    ]
);

$api->delete(
    '/feedback/{id:[0-9]+}',
    [
        'action' => 'DELETE-FEEDBACK',
        'uses'   => 'FeedbackController@delete',
    ]
);

$api->get(
    '/feedback/{id:[0-9]+}',
    [
        'action' => 'VIEW-FEEDBACK',
        'uses'   => 'FeedbackController@view',
    ]
);


$api->put('/feedback/reply/{id:[0-9]+}', [
//        'action' => 'REPLY-FEEDBACK',
        'uses'   => 'FeedbackController@reply',
    ]
);
$api->get('/reasoncancel', [
    'uses'   => 'ReasonCancelController@search',
]
);
$api->get('/client/reasoncancel', [
    'name'   => 'REASON-CANCEL',
    'action' => '',
    'uses'   => 'ReasonCancelController@getClientReason',
]
);
$api->get('/client/reasoncancel/{id:[0-9]+}', [
    'name'   => 'REASON-CANCEL',
    'action' => '',
    'uses'   => 'ReasonCancelController@getClientDetail',
]
);
$api->get('/reasoncancel/{id:[0-9]+}', [
    'uses'   => 'ReasonCancelController@detail',
]
);
$api->put('/reasoncancel/{id:[0-9]+}', [
    'uses'   => 'ReasonCancelController@update',
]
);
$api->delete('/reasoncancel/{id:[0-9]+}', [
    'uses'   => 'ReasonCancelController@delete',
]
);
$api->post('/reasoncancel', [
    'uses'   => 'ReasonCancelController@create',
]
);


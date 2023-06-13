<?php

$api->get('/hashtagcomment', [
    'uses'   => 'HashtagCommentController@search',
]
);
$api->get('/client/hashtagcomment', [
    'name'   => 'HASHTAG-COMMENT',
    'action' => '',
    'uses'   => 'HashtagCommentController@getClientReason',
]
);
$api->get('/client/hashtagcomment/{id:[0-9]+}', [
    'name'   => 'HASHTAG-COMMENT',
    'action' => '',
    'uses'   => 'HashtagCommentController@getClientDetail',
]
);
$api->get('/hashtagcomment/{id:[0-9]+}', [
    'uses'   => 'HashtagCommentController@detail',
]
);
$api->put('/hashtagcomment/{id:[0-9]+}', [
    'uses'   => 'HashtagCommentController@update',
]
);
$api->delete('/hashtagcomment/{id:[0-9]+}', [
    'uses'   => 'HashtagCommentController@delete',
]
);
$api->post('/hashtagcomment', [
    'uses'   => 'HashtagCommentController@create',
]
);
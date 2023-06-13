<?php

$api->get('/product-comments', [
//    'action' => 'VIEW-PRODUCT-COMMENT',
'uses' => 'ProductCommentController@search',
]);

$api->get('/product-comment/{id:[0-9]+}', [
//    'action' => 'VIEW-PRODUCT-COMMENT',
'uses' => 'ProductCommentController@detail',
]);

$api->post('/product-comment', [
//    'action' => 'CREATE-PRODUCT-COMMENT',
'uses' => 'ProductCommentController@create',
]);

$api->post('/product-comment-like/{id:[0-9]+}', [
    //    'action' => 'LIKE-PRODUCT-COMMENT',
'uses' => 'ProductCommentController@like',
]);

$api->put('/product-comment/{id:[0-9]+}', [
//    'action' => 'UPDATE-PRODUCT-COMMENT',
'uses' => 'ProductCommentController@update',
]);

$api->delete('/product-comment/{id:[0-9]+}', [
//    'action' => 'DELETE-PRODUCT-COMMENT',
'uses' => 'ProductCommentController@delete',
]);

############################### Question and Answer ###############################

$api->get('/product-questions', [
//    'action' => 'VIEW-PRODUCT-QUESTION',
'uses' => 'ProductCommentController@searchQuestionAnswer',
]);

$api->get('/product-question/{id:[0-9]+}', [
//    'action' => 'VIEW-PRODUCT-QUESTION',
'uses' => 'ProductCommentController@detailQuestionAnswer',
]);

$api->post('/product-question', [
//    'action' => 'CREATE-PRODUCT-QUESTION',
'uses' => 'ProductCommentController@createQuestionAnswer',
]);

$api->put('/product-question/{id:[0-9]+}', [
//    'action' => 'UPDATE-PRODUCT-QUESTION',
'uses' => 'ProductCommentController@updateQuestionAnswer',
]);

$api->delete('/product-question/{id:[0-9]+}', [
//    'action' => 'DELETE-PRODUCT-QUESTION',
'uses' => 'ProductCommentController@delete',
]);
$api->get('/product-question-comment/report', [
    'uses' => 'ProductCommentController@report',
]);

############################### Client ###############################

$api->get('/client/product-comments', [
    'name' => 'VIEW-PRODUCT-COMMENT',
    'uses' => 'ProductCommentController@searchClient',
]);

$api->get('/client/product-comment/{id:[0-9]+}', [
    'name' => 'VIEW-PRODUCT-COMMENT',
    'uses' => 'ProductCommentController@getClientProductComment',
]);
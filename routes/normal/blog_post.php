<?php
//$api->get('/client/blog/{id:[0-9]+}/posts', [
//    'action' => '',
//    'uses'   => 'BlogPostController@searchClientPost',
//]);
$api->get('/blog/post-categories', [
    'uses'   => 'BlogPostController@searchPostCategory',
]);

$api->get('/blog/post-category/{id:[0-9]+}', [
    'uses'   => 'BlogPostController@detailPostCategory',
]);

$api->get('/blog/posts', [
    'uses'   => 'BlogPostController@searchBlogPost',
]);

$api->get('/blog/post/{id:[0-9]+}', [
    'uses'   => 'BlogPostController@detailBlogPost',
]);

$api->get('/blog/post/{post_by_slug}', [
    'uses'   => 'BlogPostController@detailBlogPostBySlug',
]);

$api->get('/blog/post/{id:[0-9]+}/related-posts', [
    'uses'   => 'BlogPostController@getRelatedPost',
]);
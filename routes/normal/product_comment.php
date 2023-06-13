<?php

$api->get('/product-comments', [
    'uses' => 'ProductCommentController@search'
]);
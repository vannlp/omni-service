<?php
############ Blog ################

$api->get('/blogs', [
    'action' => 'VIEW-BLOG',
    'uses'   => 'BlogPostController@searchBlog',
]);

$api->get('/blog/{id:[0-9]+}', [
    'action' => 'VIEW-BLOG',
    'uses'   => 'BlogPostController@detailBlog',
]);

$api->post('/blog', [
    'action' => 'CREATE-BLOG',
    'uses'   => 'BlogPostController@createBlog',
]);

$api->put('/blog/{id:[0-9]+}', [
    'action' => 'UPDATE-BLOG',
    'uses'   => 'BlogPostController@updateBlog',
]);

$api->delete('/blog/{id:[0-9]+}', [
    'action' => 'DELETE-BLOG',
    'uses'   => 'BlogPostController@deleteBlog',
]);

######### Blog Categories ###############
$api->get('/blog-categories', [
    'action' => 'VIEW-BLOG-CATEGORY',
    'uses'   => 'BlogPostController@searchBlogCategory',
]);

$api->get('/blog-category/{id:[0-9]+}', [
    'action' => 'VIEW-BLOG-CATEGORY',
    'uses'   => 'BlogPostController@detailBlogCategory',
]);

$api->post('/blog-category', [
    'action' => 'CREATE-BLOG-CATEGORY',
    'uses'   => 'BlogPostController@createBlogCategory',
]);

$api->put('/blog-category/{id:[0-9]+}', [
    'action' => 'UPDATE-BLOG-CATEGORY',
    'uses'   => 'BlogPostController@updateBlogCategory',
]);

$api->delete('/blog-category/{id:[0-9]+}', [
    'action' => 'DELETE-BLOG-CATEGORY',
    'uses'   => 'BlogPostController@deleteBlogCategory',
]);

############ Post ################

$api->get('/blog/posts', [
    'action' => 'VIEW-BLOG-POST',
    'uses'   => 'BlogPostController@searchBlogPost',
]);

$api->get('/blog/post/{id:[0-9]+}', [
    'action' => 'VIEW-BLOG-POST',
    'uses'   => 'BlogPostController@detailBlogPost',
]);

$api->post('/blog/post', [
    'action' => 'CREATE-BLOG-POST',
    'uses'   => 'BlogPostController@createBlogPost',
]);

$api->put('/blog/post/{id:[0-9]+}', [
    'action' => 'UPDATE-BLOG-POST',
    'uses'   => 'BlogPostController@updateBlogPost',
]);

$api->delete('/blog/post/{id:[0-9]+}', [
    'action' => 'DELETE-BLOG-POST',
    'uses'   => 'BlogPostController@deleteBlogPost',
]);

$api->put('/blog/post/approve/{id:[0-9]+}', [
    'action' => 'UPDATE-BLOG-POST',
    'uses'   => 'BlogPostController@approveBlogPost',
]);

$api->get('/blog/post/statistics', [
    'action' => 'VIEW-BLOG-POST-STATISTIC',
    'uses'   => 'BlogPostController@searchBlogPostStatistic',
]);

$api->get('/blog/post/tags', [
    'action' => 'VIEW-BLOG-POST',
    'uses'   => 'BlogPostController@searchTagPosts',
]);


############ Post Search Histories ################

$api->get('/blog/post/top-search', [
    'action' => 'VIEW-BLOG-POST-TOP-SEARCH',
    'uses'   => 'BlogPostController@topSearch',
]);

############ Taxonomy ################

$api->get('/blog/taxonomies', [
    'action' => 'VIEW-BLOG-TAXONOMY',
    'uses'   => 'BlogPostController@searchTaxonomy',
]);

$api->get('/blog/taxonomy/{id:[0-9]+}', [
    'action' => 'VIEW-BLOG-TAXONOMY',
    'uses'   => 'BlogPostController@detailTaxonomy',
]);

$api->post('/blog/taxonomy', [
    'action' => 'CREATE-BLOG-TAXONOMY',
    'uses'   => 'BlogPostController@createTaxonomy',
]);

$api->put('/blog/taxonomy/{id:[0-9]+}', [
    'action' => 'UPDATE-BLOG-TAXONOMY',
    'uses'   => 'BlogPostController@updateTaxonomy',
]);

$api->delete('/blog/taxonomy/{id:[0-9]+}', [
    'action' => 'DELETE-BLOG-TAXONOMY',
    'uses'   => 'BlogPostController@deleteTaxonomy',
]);

############ Post Type ################

$api->get('/blog/post-types', [
    'action' => 'VIEW-BLOG-POST-TYPE',
    'uses'   => 'BlogPostController@searchPostType',
]);

$api->get('/blog/post-type/{id:[0-9]+}', [
    'action' => 'VIEW-BLOG-POST-TYPE',
    'uses'   => 'BlogPostController@detailPostType',
]);

$api->post('/blog/post-type', [
    'action' => 'CREATE-POST-TYPE',
    'uses'   => 'BlogPostController@createPostType',
]);

$api->put('/blog/post-type/{id:[0-9]+}', [
    'action' => 'UPDATE-POST-TYPE',
    'uses'   => 'BlogPostController@updatePostType',
]);

$api->delete('/blog/post-type/{id:[0-9]+}', [
    'action' => 'DELETE-POST-TYPE',
    'uses'   => 'BlogPostController@deletePostType',
]);

$api->get('/blog/post-types/detail-by-code/{post_type_code}', [
    'action' => 'VIEW-BLOG-POST-TYPE',
    'uses'   => 'BlogPostController@searchDetailCodePostType',
]);

############ Post comments #######################

$api->get('/blog/post-comments', [
    'action' => 'VIEW-POST-COMMENT',
    'uses'   => 'BlogPostController@searchPostComment',
]);

$api->get('/blog/post-comment/{id:[0-9]+}', [
    'action' => 'VIEW-POST-COMMENT',
    'uses'   => 'BlogPostController@detailPostComment',
]);

$api->post('/blog/post-comment', [
    'action' => 'CREATE-POST-COMMENT',
    'uses'   => 'BlogPostController@createPostComment',
]);

$api->put('/blog/post-comment/{id:[0-9]+}', [
    'action' => 'UPDATE-POST-COMMENT',
    'uses'   => 'BlogPostController@updatePostComment',
]);

$api->delete('/blog/post-comment/{id:[0-9]+}', [
    'action' => 'DELETE-POST-COMMENT',
    'uses'   => 'BlogPostController@deletePostComment',
]);

$api->post('/blog/post-comment/like/{id:[0-9]+}', [
    'action' => 'VIEW-POST-COMMENT',
    'uses'   => 'BlogPostController@searchPostCommentLike',
]);

################ Report Comments ########################
$api->get('/blog/report-comments', [
    'action' => 'VIEW-REPORT-COMMENT',
    'uses'   => 'BlogPostController@searchReportComment',
]);

$api->get('/blog/report-comment/{id:[0-9]+}', [
    'action' => 'VIEW-REPORT-COMMENT',
    'uses'   => 'BlogPostController@detailReportComment',
]);

$api->post('/blog/report-comment', [
    'action' => 'CREATE-REPORT-COMMENT',
    'uses'   => 'BlogPostController@createReportComment',
]);

$api->put('/blog/report-comment/{id:[0-9]+}', [
    'action' => 'UPDATE-REPORT-COMMENT',
    'uses'   => 'BlogPostController@updateReportComment',
]);

$api->delete('/blog/report-comment/{id:[0-9]+}', [
    'action' => 'DELETE-REPORT-COMMENT',
    'uses'   => 'BlogPostController@deleteReportComment',
]);

################ Post Category ########################

$api->get('/blog/post-categories', [
    'action' => 'VIEW-POST-CATEGORY',
    'uses'   => 'BlogPostController@searchPostCategory',
]);

$api->get('/blog/post-category/{id:[0-9]+}', [
    'action' => 'VIEW-POST-CATEGORY',
    'uses'   => 'BlogPostController@detailPostCategory',
]);

$api->post('/blog/post-category', [
    'action' => 'CREATE-POST-CATEGORY',
    'uses'   => 'BlogPostController@createPostCategory',
]);

$api->put('/blog/post-category/{id:[0-9]+}', [
    'action' => 'UPDATE-POST-CATEGORY',
    'uses'   => 'BlogPostController@updatePostCategory',
]);

$api->delete('/blog/post-category/{id:[0-9]+}', [
    'action' => 'DELETE-POST-CATEGORY',
    'uses'   => 'BlogPostController@deletePostCategory',
]);


####################### Client ##########################
$api->get('/client/blog/posts', [
    'name' => 'BLOG-POST-VIEW-LIST',
    'uses' => 'BlogPostController@getClientPost',
]);
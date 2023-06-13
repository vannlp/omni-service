<?php
$api->get('/client/site-info/{domain}', [
    'action' => '',
    'uses'   => 'WebsiteController@getClientWebsite',
]);

$api->get('/website/domain/{domain}', [
    'action' => '',
    'uses'   => 'WebsiteController@getClientWebsiteDomain',
]);
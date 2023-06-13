<?php
$api->get('/website/themes', [
    'action' => 'VIEW-WEBSITE-THEME',
    'uses'   => 'WebsiteThemeController@search',
]);

$api->get('/website/theme/{id:[0-9]+}', [
    'action' => 'VIEW-WEBSITE-THEME',
    'uses'   => 'WebsiteThemeController@detail',
]);

$api->post('/website/theme', [
    'action' => 'CREATE-WEBSITE-THEME',
    'uses'   => 'WebsiteThemeController@create',
]);

$api->put('/website/theme/{id:[0-9]+}', [
    'action' => 'UPDATE-WEBSITE-THEME',
    'uses'   => 'WebsiteThemeController@update',
]);

$api->delete('/website/theme/{id:[0-9]+}', [
    'action' => 'DELETE-WEBSITE-THEME',
    'uses'   => 'WebsiteThemeController@delete',
]);
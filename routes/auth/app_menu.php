<?php

$api->get('/app-menus', [
    'action' => 'VIEW-APP-MENU',
    'uses' => 'AppMenuController@search',
]);

$api->get('/app-menus/{id:[0-9]+}', [
    'action' => 'VIEW-APP-MENU',
    'uses' => 'AppMenuController@detail',
]);

$api->get('/app-menus/{code}', [
    'action' => 'VIEW-APP-MENU',
    'uses' => 'AppMenuController@view',
]);

$api->post('/app-menus', [
    'action' => 'CREATE-APP-MENU',
    'uses' => 'AppMenuController@create',
]);

$api->put('/app-menus/{id:[0-9]+}', [
    'action' => 'UPDATE-APP-MENU',
    'uses' => 'AppMenuController@update',
]);

$api->delete('/app-menus/{id:[0-9]+}', [
    'action' => 'DELETE-APP-MENU',
    'uses' => 'AppMenuController@delete',
]);

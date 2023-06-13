<?php
$api->get('/client/site-menus/{menu_code}', [
    'name'   => 'APP-MENU-VIEW-MENU',
    'action' => '',
    'uses'   => 'AppMenuController@getMenu'
]);
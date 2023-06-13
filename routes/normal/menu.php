<?php
$api->get('/client/site-menus/{menu_code}', [
    'action' => '',
    'uses'   => 'AppMenuController@getMenu'
]);
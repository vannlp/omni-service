<?php
$api->get('/client/session', [
    'name' => 'GET-SESSION',
    'uses' => 'SessionController@getSession'
]);
<?php
$api->get('/user-logs', [
    'action' => 'VIEW-LOG-USER',
    'uses'   => 'UserLogController@view',
]);
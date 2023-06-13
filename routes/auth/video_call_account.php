<?php
$api->get('/video-call-account', [
    'action' => 'VIEW-VIDEO-CALL-ACCOUNT',
    'uses'   => 'VideoCallAccountController@getAccount'
]);
<?php
$api->put('/set-offline-consultant/{socketId}', [
    'action' => '',
    'uses'   => 'ConsultantController@setOfflineConsultantBySocketId',
]);
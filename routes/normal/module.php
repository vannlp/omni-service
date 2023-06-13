<?php

$api->get('/client/module/{code}', [
    'action' => '',
    'uses'   => 'ModuleController@detail',
]);

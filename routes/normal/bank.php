<?php
$api->get('/banks', [
    'uses'   => 'BankController@getListBank'
]);
<?php

$api->get('/transaction-history', [
    //'action' => 'UPDATE-USER-LOCATION',
    'uses' => 'PaymentHistoryController@search',
]);
<?php
$api->get('/google-analytics', [
    'name' => 'VIEW-GOOGLE-ANALYTIC',
    'uses'   => 'GoogleAnalyticController@getData',
]);
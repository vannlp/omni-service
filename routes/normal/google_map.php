<?php
$api->get('/google-map/geocoding', [
    'action' => '',
    'uses' => 'GoogleMapTrontroller@geocoding',
]);
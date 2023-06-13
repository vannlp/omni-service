<?php

$api->get('/statistic-revenue-days', [
    'action' => '',
    'uses'   => 'StatisticController@statisticRevenueDay',
]);

$api->get('/statistic-revenue-months', [
    'action' => '',
    'uses'   => 'StatisticController@statisticRevenueMonth',
]);

$api->get('/statistic-revenue-years', [
    'action' => '',
    'uses'   => 'StatisticController@statisticRevenueYear',
]);

$api->get('/fast-statistics', [
    'action' => '',
    'uses'   => 'StatisticController@fastStatistics',
]);

$api->get('/statistic-order-days', [
    'action' => '',
    'uses'   => 'StatisticController@statisticOrderDay',
]);

$api->get('/statistic-order-months', [
    'action' => '',
    'uses'   => 'StatisticController@statisticOrderMonth',
]);

$api->get('/statistic-order-years', [
    'action' => '',
    'uses'   => 'StatisticController@statisticOrderYear',
]);

$api->get('/statistic-product-mosts', [
    'action' => '',
    'uses'   => 'StatisticController@statisticProductMost',
]);

$api->get('/statistic-service-mosts', [
    'action' => '',
    'uses'   => 'StatisticController@statisticServiceMost',
]);
$api->get('/statistic-top-keyword', [
    'action' => '',
    'uses'   => 'StatisticController@topkeyword',
]);
$api->get('/statistic-top-product-sale', [
    'action' => '',
    'uses'   => 'StatisticController@topsaleproduct',
]);
$api->get('/statistic-top-search-product', [
    'action' => '',
    'uses'   => 'StatisticController@topsearchproduct',
]);
$api->get('/user-analytic', [
    'action' => '',
    'uses'   => 'StatisticController@userAnalytic',
]);
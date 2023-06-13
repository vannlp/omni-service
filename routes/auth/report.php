<?php

$api->get('/report-order-by-day', [
    //'action' => 'REPORT-ORDER',
    'uses' => 'ReportController@reportOrderByDay',
]);

$api->get('/report-order-grand', [
    //'action' => 'REPORT-ORDER',
    'uses' => 'ReportController@reportOrderGrand',
]);

$api->get('/report-partner-turnover/{id:[0-9]+}', [
    //'action' => 'REPORT-ORDER',
    'uses' => 'ReportController@reportPartnerTurnover',
]);

$api->get('/report-products', [
    'action' => 'REPORT-PRODUCT',
    'uses'   => 'ReportController@reportProduct',
]);

$api->get('/report-users', [
    //'action' => 'REPORT-USER',
    'uses' => 'ReportController@reportUser',
]);

$api->get('/report-orders', [
    //'action' => 'REPORT-ORDER',
    'uses' => 'ReportController@reportOrder',
]);

$api->get('/report-partners', [
    //'action' => 'REPORT-ORDER',
    'uses' => 'ReportController@reportPartner',
]);

$api->get('/report-order-detail', [
    //'action' => 'REPORT-ORDER',
    'uses' => 'ReportController@reportOrderDetail',
]);

$api->get('/report-sale-total-by-customer', [
    //'action' => 'REPORT-ORDER',
    'uses' => 'ReportController@reportSaleTotalByCustomer',
]);

$api->get('/report-sale-total-by-month', [
    //'action' => 'REPORT-ORDER',
    'uses' => 'ReportController@reportSaleTotalByMonth',
]);

$api->get('/report-sale-by-product', [
    //'action' => 'REPORT-ORDER',
    'uses' => 'ReportController@reportSaleByProduct',
]);

$api->get('/report-customer-point', [
    //'action' => 'REPORT-USER',
    'uses' => 'ReportController@reportCustomerPoint',
]);


$api->get('/report-inventories', [
    'uses' => 'ReportController@reportInventory',
]);

$api->get('/report-listReceiveDeliver', [
    'uses' => 'ReportController@reportListReceiveDeliver',
]);

$api->get('/report-shipping-orders', [
    'uses' => 'ReportController@reportShippingOrder',
]);

$api->get('/report-user-reference-by-date', [
    'uses' => 'Report\UserReferenceController@userReferenceByDate'
]);

$api->get('/report-user-reference-by-user', [
    'uses' => 'Report\UserReferenceController@reportUserReferenceByUser'
]);

$api->get('/report-user-reference-by-user-and-date', [
    'uses' => 'Report\UserReferenceController@reportUserReferenceByUserAndDate'
]);

$api->get('/export-price-details/{id:[0-9]+}', [
    'uses' => 'ReportController@exportPriceDetail',
]);

$api->get('/print-order-bill/{id:[0-9]+}', [
    //'action' => 'PRINT-ORDER-BILL',
    'uses' => 'ReportController@printOrderBill',
]);


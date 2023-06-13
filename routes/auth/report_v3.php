<?php

$api->get('/report-v3/customer-sales', [
    'action' => 'REPORT-ORDER',
    'uses'   => 'ReportV3Controller@getCustomerSales',
]);

$api->get('/report-v3/customer-sales-details', [
    'action' => 'REPORT-ORDER',
    'uses'   => 'ReportV3Controller@getCustomerSalesDetail',
]);

$api->get('/report-v3/customer-commission', [
    'action' => 'REPORT-ORDER',
    'uses'   => 'ReportV3Controller@getCustomerCommission',
]);

$api->get('/report-v3/customer-referral-commission', [
    'action' => 'REPORT-ORDER',
    'uses'   => 'ReportV3Controller@getCustomerReferralCommission',
]);
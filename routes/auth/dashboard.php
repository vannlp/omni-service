<?php
$api->get('/dashboards/order-status', [
//    'action' => 'VIEW-DASHBOARD-ORDER',
    'uses'   => 'DashboardController@orderStatus',
]);

$api->get('/dashboards/revenue-months', [
    'action' => '',
    'uses'   => 'DashboardController@dashboardRevenueMonth',
]);

$api->get('/dashboards/top-customer-highest-sales', [
    'action' => '',
    'uses'   => 'DashboardController@topCustomerHighestSale',
]);

$api->get('/dashboards/top-product-by-customers', [
    'name'   => 'VIEW-LIST-PRODUCTS-BY-CUSTOMERS',
    'action' => '',
    'uses'   => 'DashboardController@topProductByCustomer',
]);

########################## CLIENT ##########################
// $api->get('/client/dashboards/top-product-by-customers', [
//     'name'   => 'VIEW-LIST-PRODUCTS-BY-CUSTOMERS',
//     'action' => '',
//     'uses'   => 'DashboardController@getClientTopProductByCustomer',
// ]);
$api->get('/client/dashboards/revenue-recent', [
    'name'   => 'VIEW-LIST-REVENUE-RECENT',
    'action' => '',
    'uses'   => 'DashboardController@getClientRevenueRecent',
]);
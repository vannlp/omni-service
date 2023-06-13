<?php
$api->get('/orders', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@search',
]);

$api->get('/list-orders', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@listOrder',
]);

$api->get('/orders/{id:[0-9]+}', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@detail',
]);

$api->get('/orders/get-products', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@getProducts',
]);

$api->get('/orders/get-user-hub', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@getUserHUB',
]);

$api->get('/orders/hub', [
    'uses' => 'OrderController@search',
]);

$api->post('/order/{id}/hub-update-status', [
    'uses' => 'OrderController@updateStatusOrderByHUB',
]);

$api->post('/orders', [
    'action' => 'CREATE-ORDER',
    'uses'   => 'OrderController@create',
]);
$api->put('/orders/{id:[0-9]+}', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@update',
]);
$api->put('/orders-not-details/{code}', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@updateNotDetailByCode',
]);

$api->delete('/orders/{id:[0-9]+}', [
    'action' => 'DELETE-ORDER',
    'uses'   => 'OrderController@delete',
]);

$api->get('/orders/{userId:[0-9]+}/transactions', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@paymentHistory',
]);

// Update Order Status
$api->post('/orders/{id:[0-9]+}/status', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@updateStatus',
]);

$api->put('/orders/status-crm', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@updateAdminCRM',
]);
$api->put('/orders/update-complete', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@updateCompleteOrder',
]);

$api->get('/orders/{id:[0-9]+}/status/history', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@viewStatusHistory',
]);

// History
$api->get('/orders/{id:[0-9]+}/history', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@listHistory',
]);

// Partners for Order
$api->get('/orders/{id:[0-9]+}/partners', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@listPartner',
]);

$api->get('/orders/{id:[0-9]+}/partners/assign', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@assignPartner',
]);

$api->get('/orders/{id:[0-9]+}/partners/response', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@partnerResponse',
]);
//Update Seller
$api->put('/orders-update-seller', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@updateSeller',
]);
$api->put('/orders-update-collection', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@orderCollectionCRM',
]);
// Pay an Order
$api->post('/orders/{orderId:[0-9]+}/pay', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@payOrder',
]);

//Print Order
$api->get('/orders/{id:[0-9]+}/print', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@printOrder',
]);

$api->get('/orders/{id:[0-9]+}/delivery-note', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@printDeliveryNote',
]);
////////////////// MOBILE ///////////////
$api->get('/orders/my-orders', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@listMyOrder',
]);

$api->get('/view-order-request-assign/{id:[0-9]+}', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@getMyOrder',
]);

$api->put('/orders/{id:[0-9]+}/update', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@adminUpdate',
]);
$api->put('/update-hub-orders/{code}', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@updateManyHub',
]);

$api->post('/orders/{id:[0-9]+}/rating', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@rateOrder',
]);
$api->post('/orders/{orderId:[0-9]+}/request-cancel', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@cancelOrder',
]);

$api->post('/client/orders/{orderId:[0-9]+}/request-cancel', [
    'action' => '',
    'uses'   => 'OrderController@cancelOrder',
]);

$api->put('/confirm-order', [
    'uses' => 'OrderController@userConfirmOrder',
]);

//Update Item In Order
$api->put('/update-status-item-in-order', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@updateStatusItemInOrder',
]);

// Assign to Enterprise
$api->post('/orders/{orderId:[0-9]+}/assign-enterprises', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@assignEnterprises',
]);

$api->put('/orders/{orderId:[0-9]+}/enterprise/response', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@enterpriseResponse',
]);

$api->get('/get-order-detail/{id:[0-9]+}', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@getOrderDetail',
]);
$api->get('/order-export-excel-2', [
    'action' => '',
    'uses'   => 'OrderController@orderExportExcel2',
]);
$api->get('/products-purchased', [
    'action' => '',
    'uses'   => 'OrderController@getProductPurchased',
]);
$api->get('/export-order-by-promotion', [
    'action' => '',
    'uses'   => 'OrderController@exportOrderByPromotion',
]);
// Export excel order completed

$api->get('/order-export-excel', [
    'action' => '',
    'uses'   => 'OrderController@orderExportExcel',
]);

$api->get('/client/order-export-excel', [
    'action' => '',
    'uses'   => 'OrderController@orderExportExcel',
]);

$api->put('/approve-orders/{code}', [
    'action' => 'VIEW-ORDER',
    'uses'   => 'OrderController@approvedOrderByCode',
]);

$api->put('/update-order-status/{code}', [
    'action' => 'UPDATE-ORDER',
    'uses'   => 'OrderController@updateOrderStatus',
]);

$api->get('/order-export-order', [
    'action' => '',
    'uses'   => 'OrderController@exportOrder',
]);
$api->get('/orderExport', [
    'action' => '',
    'uses'   => 'OrderController@orderListExport',
]);
$api->post('/admin-confirm-order', [
    'action' => 'CREATE-ORDER',
    'uses' => 'OrderController@adminConfirmOrder'
]);

#################################### FOR CLIENT ###############################
$api->get('client/my-order', [
    'name' => 'GET-MY-ORDER',
    'uses' => 'OrderController@clientGetMyOrder',
]);

$api->get('client/order-by-phone/{phone:[0-9]+}', [
    'name' => 'GET-ORDER-BY-PHONE',
    'uses' => 'OrderController@clientGetOrderByPhone',
]);

$api->put('client/confirm-order', [
    'name' => 'CONFIRM-ORDER',
    'uses'   => 'OrderController@confirmOrder',
]);

$api->delete('client/orders/{id:[0-9]+}', [
    'name' => 'DELETE-CLIENT-ORDER',
    'uses'   => 'OrderController@clientDelete',
]);

$api->post('/pushOrderDMS/{code}', [
    'action' => '',
    'uses'   => 'OrderController@pushOrder',
]);

$api->post('/pushStatusOrderDMS/{code}', [
    'action' => '',
    'uses'   => 'OrderController@pushStatusOrderToDMS',
]);

$api->post('/repushOrderDMS/{code}', [
    'action' => '',
    'uses'   => 'OrderController@repushOrderToDMS',
]);
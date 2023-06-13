<?php
$api->post('/payments/momo/transactionProcessor', [
    'name' => 'POST-PAYMENT',
    'uses'   => 'PaymentController@transactionProcessor',
]);

$api->post('/payments/onepay/transactionProcessor', [
    'action' => '',
    'uses'   => 'PaymentController@onePayTransactionProcessor',
]);

$api->post('/payments/zalopay/transactionProcessor', [
    'action' => '',
    'name'   => 'POST-PAYMENT',
    'uses'   => 'PaymentController@zaloPayTransactionProcessor',
]);
$api->post('/payments/shoppeepay/transactionProcessor', [
    'name' => 'POST-PAYMENT',
    'uses'   => 'PaymentController@shoppePayTransactionProcessor',
]);
$api->post('/payments/vnpay/transactionProcessor', [
    'name' => 'POST-PAYMENT',
    'uses'   => 'PaymentController@vnPayTransactionProcessor',
]);
$api->post('/payments/vpbank/transactionProcessor', [
    'name' => 'POST-PAYMENT',
    'uses'   => 'PaymentController@vpBankSessionTransactionProcessor',
]);

$api->post('/payments/initiateAuthentication/vpbank/transactionProcessor', [
    'name' => 'POST-PAYMENT',
    'uses' => 'PaymentController@initiateAuthentication',
]);

$api->post('/payments/authenticatePayer/vpbank/transactionProcessor', [
    'name' => 'POST-PAYMENT',
    'uses' => 'PaymentController@authenticatePayer',
]);

$api->post('/payments/indirectpayment/transactionProcessor', [
    'name' => 'POST-PAYMENT',
    'uses'   => 'PaymentController@updateIndirectPayment',
]);
$api->get('/payment-methods', [
    'action' => 'VIEW-PAYMENT-METHOD',
    'uses'   => 'PaymentController@getPaymentMethod',
]);
$api->put('/payments/refund/transactionProcessor/{code}', [
    'name' => 'REFUND-PAYMENT',
    'uses'   => 'PaymentController@refundPayment',
]);
################################ FOR CLIENT #############################
$api->get('/client/payment-methods', [
    'name' => 'GET-PAYMENT-METHOD',
    'uses' => 'PaymentController@getClientPaymentMethod',
]);
$api->post('/payments/virtualpayment/transactionProcessor', [
    'name' => 'POST-PAYMENT',
    'uses'   => 'PaymentController@vpBankVirtualAccountTransactionProcessor',
]);
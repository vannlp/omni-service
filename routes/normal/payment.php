<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/



$api->post('/payment/notify', function (\Illuminate\Http\Request $request) {
    $input = $request->all();

    if (!empty($input['key'])) {
        $userSession = \App\UserSession::model()->where([
            'device_token' => $input['key'],
            'deleted'      => '0',
        ])->first();
        if ($userSession) {
            // Send Notification
            $fields = [
                'data'         => [
                    'type'         => array_get($input, 'payment_type', 'MOMO'),
                    "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                ],
                'notification' => ['title' => "Back To APP", 'sound' => 'shame'],
                'to'           => $input['key'],
            ];
            $headers = ['Content-Type:application/json', 'Authorization:key=' . env("FIREBASE_SERVER_KEY", '')];
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, env('FIREBASE_URL', ''));
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

                $result = curl_exec($ch);
                if ($result === false) {
                    echo 'FCM Send Error: ' . curl_error($ch);
                }
                curl_close($ch);

                // Send Socket
                if ($userSession->socket_id && env('SOCKET_SERVER_IP', null)) {
                    $elephantVersion = new \ElephantIO\Engine\SocketIO\Version2X(env('SOCKET_SERVER_IP', null));
                    $socket = new \ElephantIO\Client($elephantVersion);
                    $socket->initialize();

                    $socket->emit('checkoutSuccess', ['type' => 'ONEPAY', 'user_socket_id' => $userSession->socket_id]);
                    $socket->close();
                }
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }

            echo "loading...";
        }
    }
});

$api->get('/onepay/returnUrl/{url}/type/{type:[a-zA-Z]+}/user/{user_id:[0-9]+}', [
    'action' => '',
    'uses'   => 'PaymentController@returnOnePay',
]);

$api->get('/onepay/returnUrl/{6}/type/{type:[a-zA-Z]+}/user/{user_id:[0-9]+}/device/{device}', [
    'action' => '',
    'uses'   => 'PaymentController@returnOnePayDevice',
]);

$api->get('/zalopay/returnUrl/{url}', [
    'action' => '',
    'uses'   => 'PaymentController@returnZaloPay',
]);
$api->get('/payment/queryStatus/{code}', [
    'action' => '',
    'uses'   => 'PaymentController@paymentStatusQuery',
]);
$api->post('/shopeepay/returnUrl', [
    'action' => '',
    'uses'   => 'PaymentController@returnCallbackShopeePay',
]);
$api->get('/shoppepay/return/{url}', [
    'action' => '',
    'uses'   => 'PaymentController@returnShopeePay',
]);
$api->post('/zalopay/callback', [
    'action' => '',
    'uses'   => 'PaymentController@returnZaloPayCallback',
]);
$api->post('/momo/callback', [
    'action' => '',
    'uses'   => 'PaymentController@returnMomoPayCallback',
]);
$api->get('/vnpay/returnUrlIPN', [
    'action' => '',
    'uses'   => 'PaymentController@returnVNPayIPN',
]);

$api->post('/vpbank/returnVpbank', [
    'action' => '',
    'uses'   => 'PaymentController@returnVpBank',
]);

$api->get('/vnpay/returnUrl/{url}', [
    'action' => '',
    'uses'   => 'PaymentController@returnVNPay',
]);
$api->get('/momo/returnUrl/{url}', [
    'action' => '',
    'uses'   => 'PaymentController@returnMomo',
]);

$api->post('/vpbank/returnPaymentVirtualAccount', [
    'action' => '',
    'uses'   => 'PaymentController@returnPaymentVirtualAccount',
]);
$api->post('/fake-status/{orderCode}', [
    'action' => '',
    'uses'   => 'PaymentController@fakeStatus',
]);

$api->post('/fake-order/{orderCode}', [
    'action' => '',
    'uses'   => 'PaymentController@fakeOrder',
]);


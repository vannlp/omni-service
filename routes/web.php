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

use App\Company;
use App\Jobs\SendCustomerMailNewOrderJob;
use App\Order;
use App\Product;
use App\Store;
use App\Supports\Message;
use App\TM;
use App\User;
use App\V1\Library\CDP;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use  \Illuminate\Http\Request;
use Monolog\Logger;


$router->get('/', function () use ($router) {
    return $router->app->version() . " - NutifoodShop API Service";
});

//$router->delete('/user/{phone}', function (\Illuminate\Http\Request $request) use ($router) {
//    $res = $request->all();
//    if (empty($res['key'])) {
//        return response()->json(['message' => "Ê! Thím có ý đồ gì đấy? Mã bí mật đâu?"], 500);
//    }
//    if ($res['key'] !== "ê!") {
//        return response()->json(['message' => "Thím rất tốt, nhưng rất tiếc, thím sai rồi!"], 500);
//    }
//    $input = $request->fullUrl();
//    $phone = explode("user/", $input);
//    $phone = $phone['1'];
//    try {
//        \Illuminate\Support\Facades\DB::beginTransaction();
//        $users = \Illuminate\Support\Facades\DB::table('users')->where('phone', $phone)->get();
//        if ($users->isEmpty()) {
//            return response()->json(['message' => "Còn méo gì đâu mà xóa!"], 500);
//        }
//        foreach ($users ?? [] as $user) {
//            $inventories = \Illuminate\Support\Facades\DB::table('inventories')->where('user_id', $user->id)->get();
//            foreach ($inventories ?? [] as $item) {
//                \Illuminate\Support\Facades\DB::table('inventory_details')->where('inventory_id', $item->id)->delete();
//                \Illuminate\Support\Facades\DB::table('inventories')->where('id', $item->id)->delete();
//            }
//            $orders = \Illuminate\Support\Facades\DB::table('orders')->where('customer_id', $user->id)->get();
//            foreach ($orders ?? [] as $item) {
//                \Illuminate\Support\Facades\DB::table('order_histories')->where('order_id', $item->id)->delete();
//                \Illuminate\Support\Facades\DB::table('order_status_histories')->where('order_id', $item->id)->delete();
//                \Illuminate\Support\Facades\DB::table('promotion_totals')->where('order_id', $item->id)->delete();
//                \Illuminate\Support\Facades\DB::table('order_details')->where('order_id', $item->id)->delete();
//                \Illuminate\Support\Facades\DB::table('shipping_histories_status')->where('shipping_id', $item->code)->delete();
//                $spos = \Illuminate\Support\Facades\DB::table('shipping_orders')->where('code', $item->code)->get();
//                if ($spos->isNotEmpty()) {
//                    foreach ($spos ?? [] as $spo) {
//                        \Illuminate\Support\Facades\DB::table('shipping_order_details')->where('shipping_order_id', $spo->id)->delete();
//                        \Illuminate\Support\Facades\DB::table('shipping_orders')->where('id', $spo->id)->delete();
//                    }
//                }
//                \Illuminate\Support\Facades\DB::table('orders')->where('id', $item->id)->delete();
//            }
//            $carts = \Illuminate\Support\Facades\DB::table('carts')->where('user_id', $user->id)->get();
//            if ($carts->isNotEmpty()) {
//                foreach ($carts ?? [] as $item) {
//                    \Illuminate\Support\Facades\DB::table('cart_details')->where('cart_id', $item->id)->delete();
//                    \Illuminate\Support\Facades\DB::table('carts')->where('id', $item->id)->delete();
//                }
//            }
//            \Illuminate\Support\Facades\DB::table('product_comments')->where('user_id', $user->id)->delete();
//            \Illuminate\Support\Facades\DB::table('shipping_address')->where('user_id', $user->id)->delete();
//            \Illuminate\Support\Facades\DB::table('user_companies')->where('user_id', $user->id)->delete();
//            \Illuminate\Support\Facades\DB::table('user_stores')->where('user_id', $user->id)->delete();
//            \Illuminate\Support\Facades\DB::table('user_logs')->where('user_id', $user->id)->delete();
//            \Illuminate\Support\Facades\DB::table('profiles')->where('user_id', $user->id)->delete();
//            \Illuminate\Support\Facades\DB::table('user_stores')->where('user_id', $user->id)->delete();
//            \Illuminate\Support\Facades\DB::table('user_companies')->where('user_id', $user->id)->delete();
//            \Illuminate\Support\Facades\DB::table('user_sessions')->where('user_id', $user->id)->delete();
//            \Illuminate\Support\Facades\DB::table('wallets')->where('user_id', $user->id)->delete();
//            \Illuminate\Support\Facades\DB::table('product_favorites')->where('user_id', $user->id)->delete();
//            \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->delete();
//        }
//        \Illuminate\Support\Facades\DB::commit();
//    } catch (\Exception $exception) {
//        \Illuminate\Support\Facades\DB::rollBack();
//        return $exception->getMessage();
//    }
//
//    return response()->json(['message' => "Xóa rồi đó thím!"]);
//});

// Authorize
$router->group(['prefix' => 'auth', 'namespace' => 'Auth', 'middleware' => ['cors2', 'trimInput']], function ($router) {
    // Auth
    $router->post('/login', "AuthController@authenticate");
    $router->post('/user-login', "AuthController@userLogin");
    $router->post('/user-register', "AuthController@userRegister");

    $router->post('/customer-login', "AuthController@customerLogin");
    $router->post('/customer-register', "AuthController@customerRegister");

    $router->get('/token', "AuthController@checkToken");
    $router->get('/logout', "AuthController@logout");
    $router->post('/forgot-password', "AuthController@forgotPassword");
    $router->post('/reset-password', "AuthController@forgotPasswordSMSVerify");

    $router->post('/social-login', "AuthController@socialLogin");
    $router->post('/mapping-user', "AuthController@socialMapping");
    $router->post('/register-and-login', "AuthController@registerAndLogin");
    $router->get('/login-zalo', "AuthController@registerAndLoginZalo");

    $router->post('/partner-register-get-sms', "AuthController@getSMSRegisterPartner");
    $router->post('/partner-register-sms', "AuthController@registerPartnerSMS");
    $router->post('/customer-register-sms', "AuthController@registerCustomerSMS");
    $router->post('/register-sms-resent', "AuthController@registerSMSResent");

    $router->post('/login-sms', "AuthController@loginSMS");
    $router->post('/login-sms-verify', "AuthController@loginSMSVerify");
    $router->post('/login-sms-resent', "AuthController@loginSMSResent");
    $router->post('/register-partner', "AuthController@registerPartner");
    // Normal API
    $router->get('/user-info', "AuthController@currentUserInfo");
});

$router->group(['prefix' => 'client', 'namespace' => 'Client', 'middleware' => ['cors2']], function ($router) {
    $router->group(['prefix' => 'auth', 'namespace' => 'Auth'], function ($router) {
        $router->get('user-group', "LoginController@getUserGroup");
        $router->get('areas', "LoginController@getAreas");
        $router->post('get-sms-code', "LoginController@getSMSCode");
        $router->post('login', "LoginController@login");
        $router->post('register', "LoginController@register");
        $router->post('reset-password', "LoginController@resetPassword");
        $router->post('forgot-password', "LoginController@sendOTPForgotPassword");
        $router->post('recaptcha/score', "LoginController@recaptchaScore");

        $router->group(['prefix' => 'reset-password'], function ($router) {
            $router->post('send-phone', "LoginController@sendPhoneResetPassword");
            $router->post('check-otp', "LoginController@checkOTPResetPassword");
            $router->post('', "LoginController@resetPassword");
        });

        $router->group(['prefix' => 'register'], function ($router) {
            $router->post('send', "LoginController@sendOTPRegister");
            $router->post('check-otp', "LoginController@checkOTPRegister");
            $router->post('sms-register', "LoginController@register");
        });
    });
});

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', [
    'middleware' => [
        'cors2',
        'trimInput',
        'verifySecret'
    ],
], function ($api) {
    $api->group(['prefix' => 'auth'], function ($api) {
        $api->options('/{any:.*}', function () {
            return response(['status' => 'success'])
                ->header('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, DELETE')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Origin');
        });
    });

    $api->group(['prefix' => 'client'], function ($api) {
        $api->options('/{any:.*}', function () {
            return response(['status' => 'success'])
                ->header('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, DELETE')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Origin');
        });
    });
});


// Normal API
require __DIR__ . '/normal/web_normal.php';

// Authorize API
require __DIR__ . '/auth/web_auth.php';

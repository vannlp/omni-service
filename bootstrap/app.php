<?php

require_once __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();


date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));
/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__ . '/../')
);


$app->instance('path.public', app()->basePath() . DIRECTORY_SEPARATOR . 'public');
$app->instance('path.config', app()->basePath() . DIRECTORY_SEPARATOR . 'config');
$app->instance('path.storage', app()->basePath() . DIRECTORY_SEPARATOR . 'storage');


$app->withFacades();
$app->withEloquent();
$app->configure('cors');
$app->configure('jwt');
$app->configure('constants');
$app->configure('messages');
$app->configure('mail');
$app->configure('import');
$app->configure('validation');
$app->configure('queue');
$app->configure('filesystems');
$app->configure('normalauth');
$app->configure('excel');
$app->configure('googlemaps');
/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Filesystem\Factory::class,
    function ($app) {
        return new Illuminate\Filesystem\FilesystemManager($app);
    }
);

$app->singleton('filesystem', function ($app) {
    return $app->loadComponent(
        'filesystems',
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        'filesystem'
    );
});

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//    App\Http\Middleware\ExampleMiddleware::class
// ]);

// $app->routeMiddleware([
//     'auth' => App\Http\Middleware\Authenticate::class,
// ]);

$app->routeMiddleware([
    'auth'         => App\Http\Middleware\Authenticate::class,
    'cors'         => \palanik\lumen\Middleware\LumenCors::class,
    'verifySecret' => App\Http\Middleware\VerifySecret::class,
    'trimInput'    => App\Http\Middleware\TrimInput::class,
    'authorize'    => App\Http\Middleware\Authorize::class,
    'cors2'        => \App\Http\Middleware\HandleCors::class,
    //'cors3'        => Barryvdh\Cors\HandleCors::class,
    'cors3'        => \Fruitcake\Cors\HandleCors::class,
    //'jwt.refresh'  => Tymon\JWTAuth\Http\Middleware\RefreshToken::class,
]);

//if (!class_exists('JWTAuth')) {
//    class_alias('Tymon\JWTAuth\Facades\JWTAuth', 'JWTAuth');
//}
//
//if (!class_exists('JWTFactory')) {
//    class_alias('Tymon\JWTAuth\Facades\JWTFactory', 'JWTFactory');
//}

if (!class_exists('QR')) {
    class_alias(SimpleSoftwareIO\QrCode\Facades\QrCode::class, 'QR');
}
/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(Dingo\Api\Provider\LumenServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
$app->register(Illuminate\Database\Eloquent\LegacyFactoryServiceProvider::class);
$app->register(\Illuminate\Mail\MailServiceProvider::class);
//$app->register(Barryvdh\Cors\ServiceProvider::class);
$app->register(Fruitcake\Cors\CorsServiceProvider::class);
$app->register(SimpleSoftwareIO\QrCode\QrCodeServiceProvider::class);
$app->register(Maatwebsite\Excel\ExcelServiceProvider::class);
$app->register(Intervention\Image\ImageServiceProvider::class);
$app->register(\Illuminate\Redis\RedisServiceProvider::class);
$app->register(GoogleMaps\ServiceProvider\GoogleMapsServiceProvider::class);
//$app->register(Spatie\Analytics\AnalyticsServiceProvider::class);
class_alias(Maatwebsite\Excel\Facades\Excel::class, "Excel");
class_alias(GoogleMaps\Facade\GoogleMapsFacade::class, "GoogleMaps");

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__ . '/../routes/web.php';
});

return $app;

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
function pdf()
{
    $path = storage_path() . '/customer/card/';
    $data = file_get_contents($path . "front.png");
    $dataFr = 'data:image/jpg;base64,' . base64_encode($data);
    $data = file_get_contents($path . "back.png");
    $dataBk = 'data:image/jpg;base64,' . base64_encode($data);

    $qrData = "data:image/png;base64," . base64_encode(\QR::format('png')
        ->size(200)
        ->errorCorrection('H')
        ->generate("12345678"));

    $view = view('customer/card/customer_card', [
        'front'      => $dataFr,
        'back'       => $dataBk,
        'card_from'  => '01/09',
        'card_name'  => 'HO SY DAI',
        'card_sccid' => '123456',
        'qr_code'    => $qrData,
    ]);
    echo $view;
    die;
}

// Normal Group
$api->version('v1', ['middleware' => ['cors']], function ($api) {
    $api->group(['prefix' => 'v0', 'namespace' => 'App\V1\Controllers'], function ($api) {

        $api->get('/', function () {
            echo "xxx";
            echo view('test/get-data');
            die;
        });

        $api->get('/img/{img}', function ($img) {
            $img = str_replace(",", "/", $img);
            public_path() . "/" . $img;

            $fp = fopen(public_path() . "/" . $img, 'rb');

            header("Content-Type: image/png");
            header("Content-Length: " . filesize(public_path() . "/" . $img));

            fpassthru($fp);
            exit;
        });

        // Setting
        require __DIR__ . '/setting.php';

        // Customer
        require __DIR__ . '/customer.php';

        //        // Category
        //        require __DIR__ . '/category.php';
    });
});

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

// Normal Group
$api->version('v1', ['middleware' => ['cors']], function ($api) {
    $api->group(['prefix' => 'v0', 'namespace' => 'App\V1\Controllers'], function ($api) {

        $api->options('/{any:.*}', function () {
            return response(['status' => 'success'])
                ->header('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, DELETE')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Origin');
        });

        $api->get('/', function () {
            return "Welcome to TM-Shine APP";
        });

        $api->get('/img/{img}', function ($img) {
            $img = str_replace(",", "/", $img);
            public_path() . "/" . $img;
            $extension = pathinfo($img, PATHINFO_EXTENSION);
            $fileName  = pathinfo($img, PATHINFO_BASENAME);
            if ($extension == 'jpg' || $extension == 'png' || $extension == 'jpeg' || $extension == 'jfif' || $extension == 'webp') {

                $fp = fopen(public_path() . "/" . $img, 'rb');

                header("Content-Type: image/png, image/jpeg, image/webp");
                header("Content-Length: " . filesize(public_path() . "/" . $img));
                fpassthru($fp);
            }

            if ($extension == 'pdf') {
                $filePdf  = public_path() . "/" . $img;
                $file     = $filePdf;
                $filename = $fileName;
                header('Content-type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Transfer-Encoding: binary');
                header('Accept-Ranges: bytes');
                @readfile($file);
                // doawload file
                header('Content-type: application/pdf');
                header('Content-Disposition: inline; filename="' . $filename . '"');
                header('Content-Transfer-Encoding: binary');
                header('Accept-Ranges: bytes');
                @readfile($file);
            }
            if ($extension == 'docx') {
                $filePdf  = public_path() . "/" . $img;
                $file     = $filePdf;
                $filename = $fileName;
                header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile($file);
            }
            if ($extension == 'xlsx' || $extension == "pptx" || $extension == "ppt") {
                $filePdf  = public_path() . "/" . $img;
                $file     = $filePdf;
                $filename = $fileName;
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'txt') {
                $filePdf  = public_path() . "/" . $img;
                $file     = $filePdf;
                $filename = $fileName;
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'doc') {
                $filePdf  = public_path() . "/" . $img;
                $file     = $filePdf;
                $filename = $fileName;
                header('Content-Type: application/msword');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'xls') {
                $filePdf  = public_path() . "/" . $img;
                $file     = $filePdf;
                $filename = $fileName;
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'zip') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header("Content-Type: application/zip");
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'rar') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: application/x-rar-compressed, application/octet-stream');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'rar5') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: application/x-rar5-compressed, application/octet-stream');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'mp3') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: audio/mpeg');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'aac') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: audio/aac');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'avi') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: video/x-msvideo');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'bin') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: application/octet-stream');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'bmp') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: image/bmp');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'csv') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: text/csv');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'gif') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: image/gif');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'ico') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: image/vnd.microsoft.icon');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'jar') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: application/java-archive');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'jar') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: application/java-archive');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'json') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: application/json');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'mid' || $extension == 'midi') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: audio/midi, audio/x-midi');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'mpeg') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: video/mpeg');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'rtf') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: application/rtf');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'tar') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: application/x-tar');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'ttf') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: font/ttf');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'wav') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: audio/wav');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'weba') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: audio/weba');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == 'webm') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: video/webm');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == '3gp') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: video/3gpp, audio/3gpp');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            if ($extension == '7z') {
                $fileZip  = public_path() . "/" . $img;
                $file     = $fileZip;
                $filename = $fileName;
                header('Content-Type: application/x-7z-compressed');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($file));
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                readfile("$file");
            }
            exit;
        });

        $api->get('/images/{img:[A-Za-z0-9./]+}', function ($img) {
            $img = str_replace(",", "/", $img);
            if (!file_exists(storage_path('uploads') . "/" . $img)) {
                echo "File not found!";
                die;
            }

            $fp = fopen(storage_path('uploads') . "/" . $img, 'rb');

            header("Content-Type: image/png");
            header("Content-Length: " . filesize(storage_path('uploads') . "/" . $img));

            fpassthru($fp);
            exit;
        });

        //Test SendMail
        $api->get('/test-send-mail', [
            'uses' => 'OrderController@sendMailConfirmOrder',
        ]);

        $api->post('/update-socket', function (\Illuminate\Http\Request $request) {
            $input = $request->all();
            if (empty($input['device_token'])) {
                return response()->json(['status' => 'error', 'message' => 'Device Token is required field!'], 400);
            }
            try {
                \Illuminate\Support\Facades\DB::beginTransaction();
                $userSession = \App\UserSession::model()
                    ->where('device_token', $input['device_token'])->where('deleted', '0')->first();
                if (empty($userSession)) {
                    return response()->json(['status' => 'error', 'message' => 'Device Token is invalid!'], 400);
                }

                $userSession->socket_id = null;
                if (isset($input['socket_id'])) {
                    $userSession->socket_id = $input['socket_id'] === 'undefined' ? null : $input['socket_id'];
                }
                $userSession->created_by = '0';
                $userSession->created_at = date("Y-m-d H:i:s", time());
                $userSession->updated_by = '0';
                $userSession->updated_at = date("Y-m-d H:i:s", time());
                $userSession->save();

                \Illuminate\Support\Facades\DB::commit();
            } catch (Exception $exception) {
                \Illuminate\Support\Facades\DB::rollBack();
                \App\Supports\TM_Error::handle($exception);
                return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 200);
            }

            return response()->json(['status' => 'success', 'message' => "Socket updated|removed successfully"], 200);
        });

        // Setting
        require __DIR__ . '/setting.php';

        // Customer
        require __DIR__ . '/customer.php';

        // Customer
        require __DIR__ . '/payment.php';

        // Image
        require __DIR__ . '/image.php';

        // Master Data
        require __DIR__ . '/master_data.php';

        // Memu
        require __DIR__ . '/menu.php';

        // Category
        require __DIR__ . '/category.php';

        // Product
        require __DIR__ . '/product.php';

        // Module
        require __DIR__ . '/module.php';

        // Area
        require __DIR__ . '/area.php';

        // Store
        require __DIR__ . '/store.php';
        //Website
        require __DIR__ . '/website.php';

        require __DIR__ . '/shipping.php';
        //Blog Post
        require __DIR__ . '/blog_post.php';

        // Consultant
        require __DIR__ . '/consultant.php';

        require __DIR__ . '/product_comment.php';

        // Brands
        require __DIR__ . '/brands.php';

        // Bank
        require __DIR__ . '/bank.php';

        require __DIR__ . '/promotion_ads.php';

        require __DIR__ . '/text_to_speech.php';
        //Order
        require __DIR__ . '/order.php';
        
        // Accesstrade
        // dimuadi
        require __DIR__ . '/dimuadi.php';
        // require __DIR__ . '/accesstrade.php';

        require __DIR__ . '/google_map.php';

        //Form register partner nutifood
        require __DIR__ . '/partner_nutifood.php';

        // RentS Grounds
        require __DIR__ . '/rent_ground.php';
        
        // Config Shipping
        require __DIR__."/config_shipping.php";
    });

    $api->group(['prefix' => 'sync', 'namespace' => 'App\Sync\Controllers'], function ($api) {

        $api->options('/{any:.*}', function () {
            return response(['status' => 'success'])
                ->header('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, DELETE')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Origin');
        });

        $api->get('/', function () {
            return "Welcome to OAM API Synchronize System";
        });

        // Viettel Sync
        require __DIR__ . '/viettel_sync.php';

        // Ninja Sync
        require __DIR__ . '/ninja_sync.php';

        // DMS SYNC
        require __DIR__ . '/dms_sync.php';

        // VPBank
        require __DIR__ . '/vpbank_sync.php';

       
    });
});

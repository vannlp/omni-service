<?php
/**
 * User: Sy Dai
 * Date: 30-Jun-18
 * Time: 22:55
 */

namespace App\Supports;

use App\Jobs\SendMailErrorJob;
use App\TM;
use Dingo\Api\Http\Response;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Monolog\Logger;

class TM_Error
{
    public static function handle(\Exception $ex)
    {
        $errorCode = $ex->getCode();
        $errorCode = empty($errorCode) ? Response::HTTP_BAD_REQUEST : $errorCode;

        try {
            $request = Request::capture();
            $param   = $request->all();
            $data    = [
                'server'  => TM::urlBase(),
                'time'    => date("Y-m-d H:i:s", time()),
                'user_id' => TM::getCurrentUserId(),
                'param'   => json_encode($param),
                'file'    => $ex->getFile(),
                'line'    => $ex->getLine(),
                'error'   => $ex->getMessage(),
            ];
            //Write Log
            TM::sendMessage('Exception: ', $data, Logger::ERROR);
        } catch (\Exception $exception) {
        }

//        if (env('APP_DEBUG', false) == true) {
//            $request = Request::capture();
//            $param   = $request->all();
//            $data    = [
//                'server'  => TM::urlBase(),
//                'time'    => date("Y-m-d H:i:s", time()),
//                'user_id' => TM::getCurrentUserId(),
//                'param'   => json_encode($param),
//                'file'    => $ex->getFile(),
//                'line'    => $ex->getLine(),
//                'error'   => $ex->getMessage(),
//            ];
//
//            //Write Log
//            TM::sendMessage('Exception: ', $data, Logger::ERROR);
//            // Send Mail
//            // dispatch(new SendMailErrorJob($data));
//        }

        if (env('APP_ENV') == 'testing') {
            $file = explode("\\", $ex->getFile());
            $file = $file[count($file) - 1];
            $file = explode("/", $file);
            $file = $file[count($file) - 1];
            $file = explode(".", $file);
            $file = $file[0];
            return ['message' => "$file:{$ex->getLine()}:" . $ex->getMessage(), 'code' => $errorCode];
        } else {
            return ['message' => Message::get("R011"), 'code' => Response::HTTP_BAD_REQUEST];
        }
    }
}
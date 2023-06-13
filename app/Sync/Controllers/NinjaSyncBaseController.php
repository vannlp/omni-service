<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:05 AM
 */

namespace App\Sync\Controllers;


use App\Supports\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller;
use Dingo\Api\Routing\Helpers;

class NinjaSyncBaseController extends Controller
{
    use Helpers;

    /**
     * SyncBaseController constructor.
     * @param Request $request
     * @throws \Exception
     */
    public function __construct(Request $request)
    {
        $headers = $request->headers->all();

        if (empty($headers['authorization'][0])) {
            throw new \Exception(Message::get("V001", "Token"));
        }

        if (strlen($headers['authorization'][0]) != 64) {
            throw new \Exception(Message::get("token_invalid"));
        }

        if ($headers['authorization'][0] != env('NINJA_SYNC_KEY', null)) {
            throw new \Exception(Message::get("token_invalid"));
        }
    }

    /**
     * @param null $msg
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseError($msg = null, $code = 400)
    {
        $msg = $msg ? $msg : Message::get("V1001");
        return response()->json(['status' => 'error', 'error' => ['errors' => ["msg" => $msg]]], $code);
    }

    /**
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseData(array $data = [])
    {
        return response()->json(['status' => 'success', 'data' => $data]);
    }
}
// 221fa1cd1377ebaaf6c5720716f7cb14ccba98c203f1a56c8d447503c28f9101
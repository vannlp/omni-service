<?php

namespace App\Supports;

use Tymon\JWTAuth\Facades\JWTAuth;
use Namshi\JOSE\JWS;

class JWTUtil
{
    static $payload;

    private static function makePayload()
    {
        if (isset(self::$payload) && !empty(self::$payload)) {
            return;
        }

        if ($token = JWTAuth::getToken()) {
            $jws = JWS::load($token);
            self::$payload = $jws->getPayload();
        }
    }

    public static function getPayloadValue($key)
    {
        self::makePayload();

        return array_get(self::$payload, $key, null);
    }
}
<?php

namespace App\Http\Middleware;

use App\Store;
use App\Supports\JWTUtil;
use App\Supports\Message;
use Closure;
use Illuminate\Support\Facades\DB;
use Mockery\CountValidator\Exception;
use Namshi\JOSE\JWS;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Support\Utils;
use Illuminate\Http\Response;

class VerifySecret
{

    /**
     * @param         $request
     * @param Closure $next
     *
     * @return mixed
     * @throws JWTException
     * @throws TokenInvalidException
     */
    public function handle($request, Closure $next)
    {
        try {
            $headers = $request->headers->all();

            if (empty($headers['authorization'])) {
                return response()->json([
                    'message'     => Message::get("V001", "Token"),
                    'status_code' => Response::HTTP_UNAUTHORIZED,
                ], Response::HTTP_UNAUTHORIZED);
            }

            // For UnAuthorize
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::where('token', $store_token_input)->exists();
                    if ($store) {
                        return $next($request);
                    }
                }
            }

            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json([
                    'message'     => Message::get('unauthorized'),
                    'status_code' => Response::HTTP_UNAUTHORIZED,
                ], Response::HTTP_UNAUTHORIZED);
            }

            $jws = JWS::load($token);
        } catch (Exception $e) {
            return response()->json([
                'message'     => Message::get("unknown", $e->getMessage()),
                'status_code' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\InvalidArgumentException $ex) {
            return response()->json([
                'message'     => $ex->getMessage(),
                'status_code' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        } catch (TokenBlacklistedException $blacklistedException) {
            return response()->json([
                'message'     => $blacklistedException->getMessage(),
                'status_code' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$jws->verify(config('jwt.secret'), config('jwt.algo'))) {
            return response()->json([
                'message'     => Message::get("token_invalid"),
                'status_code' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (Utils::timestamp(JWTUtil::getPayloadValue('exp'))->isPast()) {
            return response()->json([
                'message'     => Message::get("token_expired"),
                'status_code' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userSession = DB::table('user_sessions')->where('token', $token)->first();
        if (empty($userSession) || strtotime($userSession->expired_at) < time()) {
            return response()->json([
                'message'     => Message::get("token_expired"),
                'status_code' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (empty($userSession) || $userSession->deleted != 0) {
            return response()->json([
                'message'     => Message::get("login_other"),
                'status_code' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}

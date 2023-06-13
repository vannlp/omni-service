<?php
/**
 * User: kpistech2
 * Date: 2019-10-31
 * Time: 20:25
 */

namespace App\V1\Traits;


use App\Supports\Message;
use App\Supports\TM_Error;
use App\UserSession;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

trait AuthTrait
{
    public function logOut()
    {
        try {
            DB::beginTransaction();
            $token = JWTAuth::getToken();
            /** @var UserSession $userSession */
            $userSession = UserSession::model()->where('token', $token)->where('deleted', '0')->first();
            if (empty($userSession)) {
                return $this->response->errorBadRequest(Message::get("V002", 'Token'));
            }
            $userSession->deleted = 0;
            $userSession->save();


            $userSession->delete();
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return response()->json(['message' => 'Logout Successfully. See you again!']);
    }
}
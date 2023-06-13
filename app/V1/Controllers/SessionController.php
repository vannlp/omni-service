<?php


namespace App\V1\Controllers;


use App\Session;
use App\Store;
use App\Supports\DataUser;
use App\Supports\Message;
use App\TM;
use App\V1\Models\SessionModel;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class SessionController extends BaseController
{
    protected $model;

    /**
     * RoleController constructor.
     */
    public function __construct()
    {
        $this->model = new SessionModel();
    }

    public function getSession(Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        try {
            if (!empty($input['phone'])) {
                $result = Session::model()->where('phone', $input['phone'])->select('session_id')->first();
            } else {
                $result                 = new Session();
                $general_string         = strtoupper(TM::getCurrentUserId() . Str::random(7) . time() . $request->ip());
                $result->user_agent     = $request->header('User-Agent') ?? null;
                $result->ip             = $request->ip() ?? null;
                $result->store_id       = $store_id;
                $result->phone          = $input['phone'] ?? null;
                $result->general_string = $general_string;
                $result->session_id     = substr($general_string, 0, $l[] = rand(rand(13, 16), 25));
                $result->created_by     = TM::getCurrentUserId() ?? 1;
                $result->created_at     = date("Y-m-d H:i:s");
                $result->save();
            }
        } catch (\Exception $ex) {
            return $this->responseError($ex->getLine() . "-" . $ex->getMessage());
        }

        return ['data' => ['session_id' => $result->session_id]];
    }
}
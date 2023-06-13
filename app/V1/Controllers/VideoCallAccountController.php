<?php


namespace App\V1\Controllers;


use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\VideoCallAccountModel;
use Illuminate\Support\Facades\DB;

class VideoCallAccountController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new VideoCallAccountModel();
    }

    public function getAccount()
    {
        $data = [];
        $result = $this->model->getAccount();
        if (empty($result)) {
            try {
                DB::beginTransaction();
                $result = $this->model->createAccount();
                DB::commit();
                $data = [
                    'user_id'    => $result->user_id,
                    'phone'      => $result->phone,
                    'company_id' => TM::getCurrentCompanyId(),
                    'password'   => $result->password
                ];
            } catch (\Exception $ex) {
                DB::rollBack();
                $response = TM_Error::handle($ex);
                return $this->response->errorBadRequest($response['message']);
            }
        } else {
            $data = [
                'user_id'    => $result->user_id,
                'phone'      => $result->phone,
                'company_id' => TM::getCurrentCompanyId(),
                'password'   => $result->password
            ];
        }
        return response()->json(['data' => $data]);
    }
}
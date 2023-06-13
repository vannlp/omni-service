<?php


namespace App\V1\Controllers;


use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\UserLocationModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserLocationController extends BaseController
{
    /**
     * @var
     */
    protected $model;

    public function __construct()
    {
        $this->model = new UserLocationModel();
    }

    /**
     * @param Request $request
     * @return array|void
     */
    public function update(Request $request)
    {
        $input = $request->all();
        if (empty($input['latlong'])) {
            return $this->response->errorBadRequest(Message::get("V027"));
        }
        try {
            DB::beginTransaction();
            $userLocation = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $userLocation->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("lat_long.update-success", $input['latlong'])];
    }
}
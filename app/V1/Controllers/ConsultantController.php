<?php


namespace App\V1\Controllers;


use App\Consultant;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\ConsultantModel;
use App\V1\Transformers\Consultant\ConsultantTransformer;
use App\V1\Validators\ConsultantCreateValidator;
use App\V1\Validators\ConsultantUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultantController extends BaseController
{
    /**
     * @var ConsultantModel
     */
    protected $model;

    /**
     * ConsultantController constructor.
     */
    public function __construct()
    {
        $this->model = new ConsultantModel();
    }

    public function search(Request $request, ConsultantTransformer $consultantTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['title'])) {
            $input['title'] = ['LIKE' => $input['title']];
        }
        if (isset($input['is_online'])) {
            $input['is_online'] = ['=' => $input['is_online']];
        }
        $input['company_id'] = ['=' => TM::getCurrentCompanyId()];
        $result = $this->model->search($input, [], $limit);
        // Write Log
        Log::view($this->model->getTable());
        return $this->response->paginator($result, $consultantTransformer);
    }

    public function detail($id, ConsultantTransformer $consultantTransformer)
    {
        $result = Consultant::find($id);

        if (empty($result)) {
            return ['data' => []];
        }
        // Write Log
        Log::view($this->model->getTable());
        return $this->response->item($result, $consultantTransformer);
    }

    public function create(Request $request, ConsultantCreateValidator $consultantCreateValidator)
    {
        $input = $request->all();
        $consultantCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::create($this->model->getTable(), $result->title);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R001", $result->title)];
    }

    public function update($id, Request $request, ConsultantUpdateValidator $consultantUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $consultantUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::update($this->model->getTable(), $result->title);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R002", $result->title)];
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $result = Consultant::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get('V003', "ID #$id"));
            }
            $result->delete();
            Log::delete($this->model->getTable(), $result->title);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R003", $result->title)];
    }

    public function setOnlineConsultant(Request $request)
    {
        $input = $request->all();
        if (empty($input['socket_id'])) {
            return $this->response->errorBadRequest(Message::get("V001", "Socket ID"));
        }
        try {
            DB::beginTransaction();
            $userId = TM::getCurrentUserId();
            $result = Consultant::model()->where('user_id', $userId)->first();
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("consultants.not-exist"));
            }
            $isOnline = array_get($input, 'is_online', 0);
            $result->is_online = $isOnline;
            $result->socket_id = $input['socket_id'];
            $result->save();
            Log::update($this->model->getTable(), $result->title);
            $status = $result->is_online == 1 ? "online" : "offline";
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("consultants.update-" . $status . "-success", $result->title)];
    }

    public function setOfflineConsultantBySocketId($socketId, Request $request)
    {
        $input = $request->all();
        if (empty($socketId)) {
            return ['data' => null];
        }
        try {
            DB::beginTransaction();
            $result = Consultant::model()->where('socket_id', $socketId)->first();

            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("consultants.not-exist"));
            }
            $isOnline = array_get($input, 'is_online', 0);
            $result->is_online = $isOnline;
            $result->save();
            Log::update($this->model->getTable(), $result->title);
            $status = $result->is_online == 1 ? "online" : "offline";
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("consultants.update-" . $status . "-success", $result->title)];
    }

    public function activeConsultants(Request $request, ConsultantTransformer $consultantTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        try {
            $result = $this->model->getActiveConsultants($input, $limit);
            return $this->response->paginator($result, $consultantTransformer);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function updateConsultant(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $id = TM::getCurrentUserId();
            $result = Consultant::model()->where('user_id', $id)->first();
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("consultants.not-exist"));
            }
            $result->consultant_id = array_get($input, 'consultant_id', $result->consultant_id);
            $result->save();
            Log::update($this->model->getTable(), $result->title);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R002", $result->title)];
    }

    public function getConsultantStatus()
    {
        $userId = TM::getCurrentUserId();
        $result = Consultant::model()->where('user_id', $userId)->first();
        if (empty($result)) {
            return $this->response->errorBadRequest(Message::get("consultants.not-exist"));
        }
        $data = [
            'is_online' => $result->is_online
        ];
        return response()->json(['data' => $data]);
    }
}
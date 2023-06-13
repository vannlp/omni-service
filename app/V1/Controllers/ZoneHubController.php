<?php


namespace App\V1\Controllers;


use App\Supports\Log;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\ZoneHubModel;
use App\V1\Transformers\ZoneHub\ZoneHubTransformer;
use App\V1\Validators\ZoneHub\ZoneHubCreateValidator;
use App\V1\Validators\ZoneHub\ZoneHubUpdateValidator;
use App\ZoneHub;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZoneHubController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ZoneHubModel();
    }

    public function search(Request $request, ZoneHubTransformer $zoneHubTransformer)
    {
        $input = $request->all();
        $input['company_id'] = ['=' => TM::getCurrentCompanyId()];
        if (!empty($input['name'])) {
            $input['name'] = ['like' => "%{$input['name']}%"];
        }
        $zoneHub = $this->model->search($input, [], array_get($input, 'limit', 20));
        Log::view($this->model->getTable());
        return $this->response->paginator($zoneHub, $zoneHubTransformer);
    }

    public function detail($id, ZoneHubTransformer $zoneHubTransformer)
    {
        $zoneHub = ZoneHub::find($id);
        if (empty($zoneHub)) {
            return ['data' => null];
        }
        Log::view($this->model->getTable(), $zoneHub->name);
        return $this->response->item($zoneHub, $zoneHubTransformer);
    }

    public function create(Request $request, ZoneHubCreateValidator $zoneHubCreateValidator, ZoneHubTransformer $zoneHubTransformer)
    {
        $input = $request->all();
        $zoneHubCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $zoneHub = $this->model->upsert($input);
            Log::create($this->model->getTable(), $zoneHub->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->item($zoneHub, $zoneHubTransformer);
    }

    public function update($id, Request $request, ZoneHubUpdateValidator $zoneHubUpdateValidator, ZoneHubTransformer $zoneHubTransformer)
    {
        $input = $request->all();
        $input['id'] = $id;
        $zoneHubUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $zoneHub = $this->model->upsert($input);
            Log::update($this->model->getTable(), $zoneHub->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->item($zoneHub, $zoneHubTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $zoneHub = ZoneHub::find($id);
            if (empty($zoneHub)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete ZoneHub
            $zoneHub->delete();
            Log::delete($this->model->getTable(), "#ID:" . $zoneHub->id . "-" . $zoneHub->name);
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => 'OK', 'message' => "Delete Successful"];
    }
}
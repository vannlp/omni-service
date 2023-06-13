<?php

namespace App\V1\Controllers;

use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\ZaloModel;
use App\V1\Transformers\Zalo\ZaloTransformer;
use App\V1\Validators\ZaloCreateValidator;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZaloController extends BaseController
{
    protected $model;
    protected $client;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->model = new ZaloModel();
        $this->client = new Client();
    }

    public function show(ZaloTransformer $transformer, $storeId)
    {
        try {
            $zalo = $this->model->getFirstBy('store_id', $storeId);
            if (!$zalo) {
                return ['data' => []];
            }
            Log::view($this->model->getTable(), "#ID:" . $zalo->id);

            return $this->response->item($zalo, $transformer);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function createOrUpdate(Request $request, ZaloCreateValidator $validator, $storeId)
    {
        $input = $request->all();
        $input['store_id'] = $storeId;
        $validator->validate($input);

        try {
            DB::beginTransaction();

            $store = $this->model->getFirstBy('store_id', $storeId);
            if (!$store) {
                $result = $this->model->create($input);
                Log::create($this->model->getTable(), "#ID:" . $result->id);
                DB::commit();
                return ['status' => Message::get("wallets.create-success", $input['zalo_oaid'])];
            } else {
                $store->update($input);
                Log::update($this->model->getTable(), "#ID:" . $store->id);
                DB::commit();
                return ['status' => Message::get("roles.update-success", $input['zalo_oaid'])];
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function delete($storeId)
    {
        try {
            DB::beginTransaction();
            $zalo = $this->model->getFirstBy('store_id', $storeId);
            if (!$zalo) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$storeId"));
            }
            $zalo->delete();
            Log::delete($this->model->getTable(), "#ID:" . $zalo->id);
            DB::commit();

            return ['status' => Message::get("roles.delete-success", $zalo['zalo_oaid'])];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }
}

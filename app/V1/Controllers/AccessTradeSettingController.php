<?php

namespace App\V1\Controllers;

use App\AccessTradeSetting;
use App\Age;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\AccessTradeModel;
use App\V1\Models\AccessTradeSettingModel;
use App\V1\Models\AgeModel;
use App\V1\Transformers\AccessTradeSetting\AccessTradeSettingTransformer;
use App\V1\Validators\Age\AgeUpsertValidator;
use Illuminate\Http\Request;

class AccessTradeSettingController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new AccessTradeSettingModel();
    }

    public function search(Request $request)
    {
        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        $result              = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, new AccessTradeSettingTransformer());
    }

    public function detail($id)
    {
        $result = AccessTradeSetting::find($id);
        if (empty($result)) {
            return $this->responseError(Message::get("V003", "ID: #$id"));
        }
        return $this->response->item($result, new AccessTradeSettingTransformer());
    }
    public function detailByCategory($id)
    {
        $result = AccessTradeSetting::where('category_id',$id)->first();
        if (empty($result)) {
            return ['data' => []];
        }
        return $this->response->item($result, new AccessTradeSettingTransformer());
    }

    public function create(Request $request)
    {
        $input = $request->all();
        try {
            $result = $this->model->upsert($input);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R001', $result->id)]);
    }

    public function update($id, Request $request)
    {
        $input       = $request->all();
        $input['id'] = $id;
        try {
            $result = $this->model->upsert($input);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R002', $result->id)]);
    }

    public function delete($id)
    {
        try {
            $result = AccessTradeSetting::find($id);
            if (empty($result)) {
                return $this->responseError(Message::get("V003", "ID: #$id"));
            }
            $result->delete();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R003', $result->id)]);
    }
}
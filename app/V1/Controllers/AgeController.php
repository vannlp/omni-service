<?php

namespace App\V1\Controllers;

use App\Age;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\AgeModel;
use App\V1\Transformers\Age\AgeTransformer;
use App\V1\Validators\Age\AgeUpsertValidator;
use Illuminate\Http\Request;

class AgeController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new AgeModel();
    }

    public function search(Request $request)
    {
        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        $result              = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, new AgeTransformer());
    }

    public function detail($id)
    {
        $result = Age::find($id);
        if (empty($result)) {
            return $this->responseError(Message::get("V003", "ID: #$id"));
        }
        return $this->response->item($result, new AgeTransformer());
    }

    public function create(Request $request)
    {
        $input = $request->all();
        (new AgeUpsertValidator())->validate($input);
        try {
            $result = $this->model->upsert($input);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R001', $result->name)]);
    }

    public function update($id, Request $request)
    {
        $input       = $request->all();
        $input['id'] = $id;
        (new AgeUpsertValidator())->validate($input);
        try {
            $result = $this->model->upsert($input);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R002', $result->name)]);
    }

    public function delete($id)
    {
        try {
            $result = Age::find($id);
            if (empty($result)) {
                return $this->responseError(Message::get("V003", "ID: #$id"));
            }
            $result->delete();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R003', $result->name)]);
    }
}
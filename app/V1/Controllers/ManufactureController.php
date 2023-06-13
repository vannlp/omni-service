<?php

namespace App\V1\Controllers;

use App\Age;
use App\Manufacture;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\AgeModel;
use App\V1\Models\ManufactureModel;
use App\V1\Transformers\Age\AgeTransformer;
use App\V1\Transformers\Manufacture\ManufactureTransformer;
use App\V1\Validators\Age\AgeUpsertValidator;
use App\V1\Validators\Manufacture\ManufactureUpsertValidator;
use Illuminate\Http\Request;

class ManufactureController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ManufactureModel();
    }

    public function search(Request $request)
    {
        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        $result              = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, new ManufactureTransformer());
    }

    public function detail($id)
    {
        $result = Manufacture::find($id);
        if (empty($result)) {
            return $this->responseError(Message::get("V003", "ID: #$id"));
        }
        return $this->response->item($result, new ManufactureTransformer());
    }

    public function create(Request $request)
    {
        $input = $request->all();
        (new ManufactureUpsertValidator())->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        (new ManufactureUpsertValidator())->validate($input);
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
        (new ManufactureUpsertValidator())->validate($input);
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
            $result = Manufacture::find($id);
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
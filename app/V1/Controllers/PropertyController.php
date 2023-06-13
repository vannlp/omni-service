<?php

namespace App\V1\Controllers;

use App\Property;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\PropertyModel;
use App\V1\Transformers\Property\PropertyTransformer;
use App\V1\Validators\Property\PropertyCreateValidator;
use App\V1\Validators\Property\PropertyUpdateValidator;
use Illuminate\Http\Request;

class PropertyController extends BaseController
{
    /**
     * @var PropertyModel
     */
    protected $model;

    public function __construct()
    {
        $this->model = new PropertyModel();
    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request)
    {
        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        $result              = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, new PropertyTransformer());
    }

    /**
     * @param $id
     * @return \Dingo\Api\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function detail($id)
    {
        $result = Property::find($id);
        if (empty($result)) {
            return $this->responseError(Message::get("V003", "ID: #$id"));
        }
        return $this->response->item($result, new PropertyTransformer());
    }

    public function create(Request $request)
    {
        $input = $request->all();
        (new PropertyCreateValidator())->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        (new PropertyCreateValidator())->validate($input);
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
        (new PropertyUpdateValidator())->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        (new PropertyUpdateValidator())->validate($input);
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
            $result = Property::find($id);
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
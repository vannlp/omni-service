<?php

namespace App\V1\Controllers;

use App\Property;
use App\PropertyVariant;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\PropertyModel;
use App\V1\Models\PropertyVariantModel;
use App\V1\Transformers\PropertyVariant\PropertyVariantTransformer;
use App\V1\Validators\Property\PropertyUpdateValidator;
use App\V1\Validators\PropertyVariant\PropertyVariantCreateValidator;
use App\V1\Validators\PropertyVariant\PropertyVariantUpdateValidator;
use Illuminate\Http\Request;

class PropertyVariantController extends BaseController
{
    /**
     * @var PropertyVariantModel
     */
    protected $model;

    public function __construct()
    {
        $this->model = new PropertyVariantModel();
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
        return $this->response->paginator($result, new PropertyVariantTransformer());
    }

    /**
     * @param $id
     * @return \Dingo\Api\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function detail($id)
    {
        $result = PropertyVariant::find($id);
        if (empty($result)) {
            return $this->responseError(Message::get("V003", "ID: #$id"));
        }
        return $this->response->item($result, new PropertyVariantTransformer());
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $input = $request->all();
        (new PropertyVariantCreateValidator())->validate($input);
        try {
            $result = $this->model->upsert($input);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R001', $result->name)]);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $input       = $request->all();
        $input['id'] = $id;
        (new PropertyVariantUpdateValidator())->validate($input);
        try {
            $result = $this->model->upsert($input);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R002', $result->name)]);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        try {
            $result = PropertyVariant::find($id);
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
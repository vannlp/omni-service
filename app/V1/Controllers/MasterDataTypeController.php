<?php
/**
 * Date: 2/23/2019
 * Time: 1:48 PM
 */

namespace App\V1\Controllers;


use App\MasterData;
use App\MasterDataType;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\MasterDataTypeModel;
use App\V1\Traits\ControllerTrait;
use App\V1\Transformers\MasterDataType\MasterDataTypeTransformer;
use App\V1\Validators\MasterDataTypeCreateValidator;
use App\V1\Validators\MasterDataTypeUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterDataTypeController extends BaseController
{
    use ControllerTrait;
    /**
     * @var MasterDataTypeModel
     */
    protected $model;

    public function __construct()
    {
        $this->model = new MasterDataTypeModel();
    }

    /**
     * @param Request $request
     * @param MasterDataTypeTransformer $masterDataTypeTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, MasterDataTypeTransformer $masterDataTypeTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $masterDataType = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($masterDataType, $masterDataTypeTransformer);
    }

    public function detail($id, MasterDataTypeTransformer $masterDataTypeTransformer)
    {
        $masterDataType = MasterDataType::find($id);
        if (empty($masterDataType)) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($masterDataType, $masterDataTypeTransformer);
    }

    public function create(Request $request, MasterDataTypeCreateValidator $masterDataTypeCreateValidator, MasterDataTypeTransformer $masterDataTypeTransformer)
    {
        $input = $request->all();
        $masterDataTypeCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $masterDataType = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $masterDataType->id . "-" . $masterDataType->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("master_data_type.create-success", $masterDataType->type)];
    }

    public function update($id, Request $request, MasterDataTypeUpdateValidator $masterDataTypeUpdateValidator, MasterDataTypeTransformer $masterDataTypeTransformer)
    {
        $input = $request->all();
        $input['id'] = $id;
        $masterDataTypeUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $masterDataType = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $masterDataType->id, null, $masterDataType->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("master_data_type.update-success", $masterDataType->type)];
    }

    /**
     * @param $id
     *
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $masterDataType = MasterDataType::find($id);
            if (empty($masterDataType)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $masterData = MasterData::where('type', $masterDataType->type)->first();
            if (!empty($masterData)) {
                return $this->response->errorBadRequest(Message::get("V030", "TYPE #" . $masterDataType->type));
            }
            // 1. Delete MasterDataType
            $masterDataType->delete();
            Log::delete($this->model->getTable(), "#ID:" . $masterDataType->id . "-" . $masterDataType->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("master_data_type.delete-success", $masterDataType->type)];
    }

}
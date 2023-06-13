<?php
/**
 * Date: 2/21/2019
 * Time: 11:50 AM
 */

namespace App\V1\Controllers;


use App\MasterData;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Controllers\BaseController;
use App\V1\Models\MasterDataModel;
use App\V1\Traits\ControllerTrait;
use App\V1\Transformers\MasterData\MasterDataTransformer;
use App\V1\Validators\MasterDataCreateValidator;
use App\V1\Validators\MasterDataUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterDataController extends BaseController
{
    use ControllerTrait;
    /**
     * @var MasterDataModel
     */
    protected $model;

    /**
     * MasterController constructor.
     */
    public function __construct()
    {
        $this->model = new MasterDataModel();
    }

    /**
     * @param $id
     * @param MasterDataTransformer $masterDataTransformer
     * @return array|\Dingo\Api\Http\Response
     */
    public function search(Request $request, MasterDataTransformer $masterDataTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $masterData = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($masterData, $masterDataTransformer);
    }

    public function searchNotLogin(Request $request, MasterDataTransformer $masterDataTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $masterData = $this->model->searchNotLogin($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($masterData, $masterDataTransformer);
    }

    public function view($id, MasterDataTransformer $masterDataTransformer)
    {
        $masterData = MasterData::find($id);
        if (empty($master)) {
            return ["data" => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($masterData, $masterDataTransformer);
    }

    public function create(Request $request, MasterDataCreateValidator $masterDataCreateValidator, MasterDataTransformer $masterDataTransformer)
    {
        $input = $request->all();
        $masterDataCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $masterData = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $masterData->id . "-" . $masterData->code . "-" . $masterData->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("master_data.create-success", $masterData->name)];
    }

    public function update($id, Request $request, MasterDataUpdateValidator $masterDataUpdateValidator, MasterDataTransformer $masterDataTransformer)
    {
        $input = $request->all();
        $input['id'] = $id;
        $masterDataUpdateValidator->validate($input);
        try {return $this->responseError(Message::get("V028", $input['code']));
            if (!empty($input['code'])) {
                $item = MasterData::model()->where('code', $input['code'])->first();
                if (!empty($item) && $item->id != $input['id']) {
                    return $this->responseError(Message::get("V028", $input['code']));
                }
            }
            DB::beginTransaction();
            $masterData = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $masterData->id, null, $masterData->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("master_data.update-success", $masterData->name)];
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $masterData = MasterData::find($id);
            if (empty($masterData)) {
                return $this->response->errorBadRequest(Message::get("V003", "TYPE #$id)"));
            }
            // 1. Delete Master Data
            $masterData->delete();
            Log::delete($this->model->getTable(), "#ID:" . $masterData->id . "-" . $masterData->name);
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("master_data.delete-success", $masterData->name)];
    }
}

<?php

namespace App\V1\Controllers;

use App\Supports\Log;
use App\Supports\TM_Error;
use App\Supports\Message;
use App\V1\Models\ModuleModel;
use App\V1\Transformers\Module\ModuleTransformer;
use App\V1\Validators\ModuleCreateValidator;
use App\V1\Validators\ModuleUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuleController extends BaseController
{
    public function __construct()
    {
        $this->model = new ModuleModel();
    }

    public function search(Request $request, ModuleTransformer $transformer)
    {
        try {
            $input = $request->all();
            $limit = array_get($input, 'limit', 20);
            $result = $this->model->search($input, ['company'], $limit);
            Log::view($this->model->getTable());
            return $this->response->paginator($result, $transformer);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function detail($code, ModuleTransformer $transformer)
    {
        try {
            $result = $this->model->getFirstBy('module_code', $code);
            if (empty($result)) {
                return ["data" => []];
            }

            Log::view($this->model->getTable());
            return $this->response->item($result, $transformer);
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
    }

    public function store(Request $request, ModuleCreateValidator $validator)
    {
        $input = $request->all();
        $validator->validate($input);

        try {
            DB::beginTransaction();
            $result = $this->model->create($input);
            Log::create($this->model->getTable(), $result->title);
            DB::commit();

            return ['status' => Message::get("folders.create-success", $result->module_name)];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function update($id, Request $request, ModuleUpdateValidator $validator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $validator->validate($input);

        try {
            DB::beginTransaction();
            $result = $this->model->update($input);
            Log::update($this->model->getTable(), "#ID:" . $result->id);
            DB::commit();

            return ['status' => Message::get("folders.update-success", $result->module_name)];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function delete($id)
    {
        try {
            $module = $this->model->getFirstBy('id', $id);
            if (!$module) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            DB::beginTransaction();
            $module->delete();
            Log::delete($this->model->getTable(), "#ID:" . $id);
            DB::commit();

            return ['status' => Message::get("department.delete-success", $module->module_name)];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }
}
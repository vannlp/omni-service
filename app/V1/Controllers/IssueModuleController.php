<?php


namespace App\V1\Controllers;


use App\IssueModule;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\IssueModuleModel;
use App\V1\Transformers\IssueModule\IssueModuleTransformer;
use App\V1\Validators\IssueModule\IssueModuleCreateValidator;
use App\V1\Validators\IssueModule\IssueModuleUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IssueModuleController extends BaseController
{
    /**
     * @var IssueModuleModel
     */
    protected $model;

    /**
     * ModuleCategoryController constructor.
     */

    public function __construct()
    {
        $this->model = new IssueModuleModel();
    }


    public function search(Request $request, IssueModuleTransformer $issueModuleTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $result = $this->model->search($input, [], $limit);
        //   Log::view($this->model->getTable());
        return $this->response->paginator($result, $issueModuleTransformer);
    }


    public function detail($id, IssueModuleTransformer $issueModuleTransformer)
    {
        try {
            $result = $this->model->getFirstBy('id', $id);
            // Log::view($this->model->getTable());
            if (empty($result)) {
                return ["data" => []];
            }
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->item($result, $issueModuleTransformer);
    }


    public function create(
        Request $request,
        IssueModuleCreateValidator $issueModuleCreateValidator,
        IssueModuleTransformer $issueModuleTransformer
    )
    {
        $input = $request->all();
        $issueModuleCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::create($this->model->getTable(), $result->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($result, $issueModuleTransformer);
    }

    public function update(
        $id,
        Request $request,
        IssueModuleUpdateValidator $issueModuleUpdateValidator,
        IssueModuleTransformer $issueModuleTransformer
    )
    {
        $input = $request->all();
        $input['id'] = $id;
        $issueModuleUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::update($this->model->getTable(), $result->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($result, $issueModuleTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $result = IssueModule::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete Module
            $result->delete();
            Log::delete($this->model->getTable(), $result->name);
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R003", $result->code)];
    }
}
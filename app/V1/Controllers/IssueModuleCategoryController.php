<?php


namespace App\V1\Controllers;


use App\IssueModuleCategory;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\IssueModuleCategoryModel;
use App\V1\Transformers\IssueModuleCategory\IssueModuleCategoryTransformer;
use App\V1\Validators\IssueModuleCategory\IssueModuleCategoryCreateValidator;
use App\V1\Validators\IssueModuleCategory\IssueModuleCategoryUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IssueModuleCategoryController extends BaseController
{
    /**
     * @var IssueModuleCategoryModel
     */
    protected $model;

    /**
     * ModuleCategoryController constructor.
     */

    public function __construct()
    {
        $this->model = new IssueModuleCategoryModel();
    }

    /**
     * @param Request $request
     * @param IssueModuleCategoryTransformer $issueModuleCategoryTransformer
     * @return mixed
     */

    public function search(Request $request, IssueModuleCategoryTransformer $issueModuleCategoryTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $result = $this->model->search($input, [], $limit);
        // Log::view($this->model->getTable());
        return $this->response->paginator($result, $issueModuleCategoryTransformer);
    }


    public function detail($id, IssueModuleCategoryTransformer $issueModuleCategoryTransformer)
    {
        try {
            $result = $this->model->getFirstBy('id', $id);
            //    Log::view($this->model->getTable());
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
        return $this->response->item($result, $issueModuleCategoryTransformer);
    }


    public function create(
        Request $request,
        IssueModuleCategoryCreateValidator $issueModuleCategoryCreateValidator,
        IssueModuleCategoryTransformer $issueModuleCategoryTransformer)
    {
        $input = $request->all();
        $issueModuleCategoryCreateValidator->validate($input);
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
        return $this->response->item($result, $issueModuleCategoryTransformer);
    }

    public function update(
        $id,
        Request $request,
        IssueModuleCategoryUpdateValidator $issueModuleCategoryUpdateValidator,
        IssueModuleCategoryTransformer $issueModuleCategoryTransformer
    )
    {
        $input = $request->all();
        $input['id'] = $id;
        $issueModuleCategoryUpdateValidator->validate($input);

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
        return $this->response->item($result, $issueModuleCategoryTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $result = IssueModuleCategory::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete ModuleCategory
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
<?php
/**
 * User: Administrator
 * Date: 12/10/2018
 * Time: 06:27 PM
 */

namespace App\V1\Controllers;


use App\PermissionGroup;
use App\Supports\Log;
use App\Supports\TM_Error;
use App\V1\Models\PermissionGroupModel;
use App\V1\Transformers\PermissionGroup\PermissionGroupTransformer;
use App\V1\Validators\PermissionGroupCreateValidator;
use App\V1\Validators\PermissionGroupUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionGroupController extends BaseController
{
    /**
     * @var PermissionGroupModel
     */
    protected $model;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->model = new PermissionGroupModel();
    }

    /**
     * @param Request $request
     * @param PermissionGroupTransformer $permissionGroupTransformer
     * @return \Dingo\Api\Http\Response
     */

    public function search(Request $request, PermissionGroupTransformer $permissionGroupTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $permissionGroupModel = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($permissionGroupModel, $permissionGroupTransformer);
    }

    public function detail($id, PermissionGroupTransformer $permissionGroupTransformer)
    {
        try {
            $result = PermissionGroup::find($id);
            if (empty($result)) {
                return ["data" => []];
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->item($result, $permissionGroupTransformer);
    }

    public function create(
        Request $request,
        PermissionGroupCreateValidator $permissionGroupCreateValidator,
        PermissionGroupTransformer $permissionGroupTransformer
    )
    {
        $input = $request->all();
        $permissionGroupCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $permissionGroupModel = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $permissionGroupModel->id . "-" . $permissionGroupModel->code . "-" . $permissionGroupModel->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($permissionGroupModel, $permissionGroupTransformer);
    }

    public function update(
        $id,
        Request $request,
        PermissionGroupUpdateValidator $permissionGroupUpdateValidator,
        PermissionGroupTransformer $permissionGroupTransformer
    )
    {
        $input = $request->all();
        $input['id'] = $id;
        $permissionGroupUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $permissionGroupModel = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $permissionGroupModel->id, null, $permissionGroupModel->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($permissionGroupModel, $permissionGroupTransformer);
    }
}
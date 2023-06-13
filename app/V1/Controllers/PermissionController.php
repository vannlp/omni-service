<?php
/**
 * User: Administrator
 * Date: 12/10/2018
 * Time: 06:27 PM
 */

namespace App\V1\Controllers;


use App\Permission;

use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\PermissionModel;
use App\V1\Traits\ControllerTrait;
use App\V1\Transformers\Permission\PermissionTransformer;
use App\V1\Validators\PermissionCreateValidator;
use App\V1\Validators\PermissionUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Dingo\Api\Http\Response;

class PermissionController extends BaseController
{
    use ControllerTrait;
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
        $this->model = new PermissionModel();
    }

    /**
     * @param Request $request
     * @param PermissionTransformer $permissionTransformer
     * @return \Dingo\Api\Http\Response
     */

    public function search(Request $request, PermissionTransformer $permissionTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $permissionModel = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($permissionModel, $permissionTransformer);
    }

    public function allGroup(Request $request, PermissionTransformer $permissionTransformer)
    {
        $input = $request->all();
        try {
            $permissions = $this->model->search($input, ['permissionGroup']);

            $result = [];
            if (!empty($permissions)) {
                foreach ($permissions as $permission) {
                    $groupId = empty($permission->group_id) ? "ZZZ" : $permission->group_id;
                    $result[$groupId]['group_id'] = object_get($permission, 'permissionGroup.id', null);
                    $result[$groupId]['group_name'] = object_get($permission, 'permissionGroup.name', null);
                    $result[$groupId]['group_code'] = object_get($permission, 'permissionGroup.code', null);
                    $result[$groupId]['is_active'] = object_get($permission, 'permissionGroup.is_active', null);
                    $result[$groupId]['permissions'][] = [
                        'permission_name'        => $permission->name,
                        'permission_code'        => $permission->code,
                        'permission_description' => $permission->description,
                        'permission_id'          => $permission->id,
                        'permission_group_id'    => $permission->group_id,
                        'permission_group_name'  => object_get($permission, 'permissionGroup.name', null),
                        'permission_group_code'  => object_get($permission, 'permissionGroup.code', null),
                    ];
                }

                $result = array_values($result);
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return ['status' => Response::HTTP_OK, 'data' => $result];
    }

    public function detail($id, PermissionTransformer $permissionTransformer)
    {
        try {
            $permissions = $this->model->getFirstBy('id', $id);
            if (empty($permissions)) {
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

        return $this->response->item($permissions, $permissionTransformer);
    }

    public function create(
        Request $request,
        PermissionCreateValidator $permissionCreateValidator,
        PermissionTransformer $permissionTransformer
    )
    {
        $input = $request->all();
        $permissionCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $permissionModel = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $permissionModel->id . "-" . $permissionModel->code . "-" . $permissionModel->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($permissionModel, $permissionTransformer);
    }

    public function update(
        $id,
        Request $request,
        PermissionUpdateValidator $permissionUpdateValidator,
        PermissionTransformer $permissionTransformer
    )
    {
        $input = $request->all();
        $input['id'] = $id;
        $permissionUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $permissionModel = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $permissionModel->id, null, $permissionModel->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($permissionModel, $permissionTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $permission = Permission::find($id);
            if (empty($permission)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            $this->checkForeignTable($id, config("constants.FT.{$this->model->getTable()}", []));

            // 1. Delete PerMission
            $permission->delete();
            Log::delete($this->model->getTable(), "#ID:" . $permission->id . "-" . $permission->name);
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("permission.delete-success", $permission->code)];
    }
}
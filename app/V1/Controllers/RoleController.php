<?php
/**
 * User: Administrator
 * Date: 12/10/2018
 * Time: 06:27 PM
 */

namespace App\V1\Controllers;


use App\Role;
use App\RolePermission;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\RoleModel;
use App\V1\Models\RolePermissionModel;
use App\V1\Transformers\Role\RoleTransformer;
use App\V1\Validators\RoleValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class RoleController extends BaseController
{
    protected $model;

    /**
     * RoleController constructor.
     */
    public function __construct()
    {
        $this->model = new RoleModel();
    }

    /**
     * @param Request $request
     * @param RoleTransformer $roleTransformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function checkForeignTable($id, $tables)
    {
        if (empty($tables)) {
            return true;
        }

        $result = "";

        foreach ($tables as $table_key => $table) {
            $temp = explode(".", $table_key);
            $table_name = $temp[0];
            $foreign_key = !empty($temp[1]) ? $temp[1] : 'id';
            $data = DB::table($table_name)->where($foreign_key, $id)->first();
            if (!empty($data)) {
                $result .= "$table; ";
            }
        }

        $result = trim($result, "; ");

        if (!empty($result)) {
            return $this->response->errorBadRequest(Message::get("R004", $result));
        }

        return true;
    }

    public function search(Request $request, RoleTransformer $roleTransformer)
    {
        $input = $request->all();

        try {
            $roles = $this->model->search($input, [], array_get($input, 'limit', 20));
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        return $this->response->paginator($roles, $roleTransformer);
    }

    /**
     * @param $id
     * @param RoleTransformer $roleTransformer
     *
     * @return \Dingo\Api\Http\Response
     */
    public function detail($id, RoleTransformer $roleTransformer)
    {
        try {
            $role = $this->model->getFirstBy('id', $id);
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->item($role, $roleTransformer);
    }

    public function store(Request $request, RoleValidator $roleValidator, RoleTransformer $roleTransformer)
    {
        $input = $request->all();
        $roleValidator->validate($input);

        try {
            $role = $this->model->upsert($input);
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->item($role, $roleTransformer);
    }

    public function update($id, Request $request, RoleValidator $roleValidator, RoleTransformer $roleTransformer)
    {
        $input = $request->all();
        $input['id'] = $id;
        $roleValidator->validate($input);
        try {
            DB::beginTransaction();
            $role = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $role->id, null, $role->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($role, $roleTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $role = Role::find($id);
            if (empty($role)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete Role
            $role->delete();
            Log::delete($this->model->getTable(), "#ID:" . $role->id . "-" . $role->name);
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => 'OK', 'message' => "Delete Successful"];
    }

    public function addPermission($id, Request $request)
    {
        $input = $request->all();
        try {
            $allPermission = RolePermission::model()->where('role_id', $id)->get()->toArray();
            $allPermission = array_pluck($allPermission, 'role_id', 'permission_id');

            // 1. Add new Permission
            DB::beginTransaction();
            $rolePermissionModel = new RolePermissionModel();
            foreach ($input['permission_id'] as $permission_id) {
                if (empty($allPermission[$permission_id])) {
                    // Add role
                    $rolePermission = DB::table($rolePermissionModel->getTable())
                        ->where('permission_id', $permission_id)
                        ->where('role_id', $id)
                        ->first();
                    if (!empty($rolePermission)) {
                        DB::table($rolePermissionModel->getTable())
                            ->where('permission_id', $permission_id)
                            ->where('role_id', $id)
                            ->update([
                                'deleted_at' => null,
                                'deleted_by' => null
                            ]);
                    } else {
                        $rolePermissionModel->refreshModel();
                        $rolePermissionModel->create(['role_id' => $id, 'permission_id' => $permission_id]);
                    }
                } else {
                    unset($allPermission[$permission_id]);
                }
            }
            //2 . Delete role permission
            foreach ($allPermission as $permission_id => $role_id) {
                $rolePermissionModel->refreshModel();
                $rolePermissionModel->deleteBy(['role_id', 'permission_id'], [
                    'role_id'       => $role_id,
                    'permission_id' => $permission_id,
                ]);
            }
            Log::create($rolePermissionModel->getTable());
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => 'Ok', 'message' => "Update Role Successful!"];
    }
}

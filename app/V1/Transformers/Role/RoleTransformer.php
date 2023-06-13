<?php
/**
 * User: Administrator
 * Date: 12/10/2018
 * Time: 07:25 PM
 */

namespace App\V1\Transformers\Role;


use App\Role;
use App\RolePermission;
use App\Supports\TM_Error;
use App\V1\Models\PermissionModel;
use App\V1\Models\RolePermissionModel;
use League\Fractal\TransformerAbstract;
use phpDocumentor\Reflection\Types\Object_;

class RoleTransformer extends TransformerAbstract
{
    public function transform(Role $role)
    {
        try {
            $permissionModel = new PermissionModel();
            $rolePermissionModel = new RolePermissionModel();
            $roles = RolePermission::model()
                ->select([
                    $permissionModel->getTable() . '.name as permission_name',
                    $permissionModel->getTable() . '.code as permission_code'
                ])
                ->where('role_id', $role->id)
                ->whereNull($permissionModel->getTable() . '.deleted_at')
                ->join($permissionModel->getTable(), $permissionModel->getTable() . '.id', '=',
                    $rolePermissionModel->getTable() . '.permission_id')
                ->get()->toArray();

            $permissions = array_pluck($roles, "permission_code");

            return [
                'id'          => $role->id,
                'code'        => $role->code,
                'name'        => $role->name,
                'description' => $role->description,
                'permissions' => $permissions,
                'is_active'   => $role->is_active,
                'role_level'  => $role->role_level,
                'role_group'  => $role->roleGroup->code,
                'created_at'  => date('d-m-Y', strtotime($role->created_at)),
                'updated_at'  => date('d-m-Y', strtotime($role->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

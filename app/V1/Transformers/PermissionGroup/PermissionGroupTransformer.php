<?php
/**
 * Date: 1/11/2019
 * Time: 9:26 AM
 */

namespace App\V1\Transformers\PermissionGroup;

use App\PermissionGroup;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

/**
 * Class PermissionGroupTransformer
 * @package App\V1\Transformers\PermissionGroup
 */
class PermissionGroupTransformer extends TransformerAbstract
{
    /**
     * @param PermissionGroup $permissionGroup
     * @return array
     * @throws \Exception
     */
    public function transform(PermissionGroup $permissionGroup)
    {
        try {

            $permissions = object_get($permissionGroup, 'permissions', []);
            if (!empty($permissions)) {
                $permissions = $permissions->toArray();
                $permissions = array_map(function ($permission) {
                    return [
                        'id'   => $permission['id'],
                        'name' => $permission['name'],
                        'code' => $permission['code'],
                    ];
                }, $permissions);
            }

            return [
                'id' => $permissionGroup->id,
                'code' => $permissionGroup->code,
                'name' => $permissionGroup->name,
                'description' => $permissionGroup->description,
                'is_active' => $permissionGroup->is_active,
                'permissions' => $permissions,
                'created_at' => date('d-m-Y', strtotime($permissionGroup->created_at)),
                'updated_at' =>  date('d-m-Y', strtotime($permissionGroup->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }

}
<?php
/**
 * User: SangNguyen
 * Date: 3/27/2019
 * Time: 9:20 PM
 */

namespace App\V1\Transformers\Permission;

use App\Permission;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

/**
 * Class PermissionTransformer
 * @package App\V1\Transformers\Permission
 */
class PermissionTransformer extends TransformerAbstract
{
    /**
     * @param Permission $permission
     * @return array
     * @throws \Exception
     */
    public function transform(Permission $permission)
    {
        try {
            return [
                'id'                    => $permission->id,
                'code'                  => $permission->code,
                'name'                  => $permission->name,
                'description'           => $permission->description,
                'permission_group_id'   => $permission->group_id,
                'permission_group_name' => object_get($permission, 'permissionGroup.name'),
                'permission_group_code' => object_get($permission, 'permissionGroup.code'),
                'is_active'             => $permission->is_active,
                'created_at'            => date('d-m-Y', strtotime($permission->created_at)),
                'updated_at'            => !empty($permission->updated_at) ? date('d-m-Y',
                    strtotime($permission->updated_at)) : null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
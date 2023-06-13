<?php
/**
 * User: Administrator
 * Date: 12/10/2018
 * Time: 06:35 PM
 */

namespace App\V1\Models;


use App\Permission;
use App\Supports\Message;
use App\TM;

class PermissionModel extends AbstractModel
{
    public function __construct(Permission $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $permission = Permission::find($id);
            if (empty($permission)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $permission->name = array_get($input, 'name', $permission->name);
            $permission->code = array_get($input, 'code', $permission->code);
            $permission->group_id = array_get($input, 'group_id', $permission->group_id);
            $permission->description = array_get($input, 'description', NULL);
            $permission->updated_at = date("Y-m-d H:i:s", time());
            $permission->updated_by = TM::getCurrentUserId();
            $permission->save();
        } else {
            $param = [
                'code'        => $input['code'],
                'name'        => $input['name'],
                'group_id'    => $input['group_id'],
                'description' => array_get($input, 'description'),
                'is_active'   => 1,

            ];

            $permission = $this->create($param);
        }

        return $permission;
    }
}
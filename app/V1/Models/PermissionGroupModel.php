<?php
/**
 * User: Administrator
 * Date: 12/10/2018
 * Time: 07:19 PM
 */

namespace App\V1\Models;


use App\PermissionGroup;
use App\Supports\Message;
use App\TM;

class PermissionGroupModel extends AbstractModel
{
    public function __construct(PermissionGroup $model = null)
    {
        parent::__construct($model);
    }
    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $permissionGroup = PermissionGroup::find($id);
            if (empty($permissionGroup)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $permissionGroup->name = array_get($input, 'name', $permissionGroup->name);
            $permissionGroup->code = array_get($input, 'code', $permissionGroup->code);
            $permissionGroup->description = array_get($input, 'description', NULL);
            $permissionGroup->updated_at = date("Y-m-d H:i:s", time());
            $permissionGroup->updated_by = TM::getCurrentUserId();
            $permissionGroup->save();
        } else {
            $param = [
                'code'        => $input['code'],
                'name'        => $input['name'],
                'description' => array_get($input, 'description'),
                'is_active'   => 1,

            ];

            $permissionGroup = $this->create($param);
        }

        return $permissionGroup;
    }
}
<?php
/**
 * User: Administrator
 * Date: 12/10/2018
 * Time: 06:33 PM
 */

namespace App\V1\Models;


use App\Role;
use App\RoleGroup;
use App\Supports\Message;
use App\TM;

class RoleModel extends AbstractModel
{
    public function __construct(Role $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param array $input
     * @param array $with
     * @param null $limit
     * @return mixed
     */
    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $roleLevel = TM::info()['role_level'];
        if(TM::info()['role_code'] != USER_ROLE_SUPERADMIN) {
            $query->where('role_level','>=',$roleLevel);
        }
        $this->sortBuilder($query, $input);

        if ($limit) {
            return $query->paginate($limit);
        } else {
            return $query->get();
        }
    }

    /**
     * @param $input
     * @return mixed
     * @throws \Exception
     */
    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;

        $group = RoleGroup::model()
            ->where('code',$input['role_group'])->first();
        $input['role_group'] = $group->id;

        if ($id) {
            $role = Role::find($id);
            if (empty($role)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $role->name = array_get($input, 'name', $role->name);
            $role->code = array_get($input, 'code', $role->code);
            $role->description = array_get($input, 'description', $role->description);
            $role->role_level = array_get($input, 'role_level', $role->role_level);
            $role->role_group = array_get($input, 'role_group', $role->role_group);
            $role->updated_at = date("Y-m-d H:i:s", time());
            $role->updated_by = TM::getCurrentUserId();
            $role->save();
        } else {
            $param = [
                'code'        => $input['code'],
                'name'        => $input['name'],
                'description' => array_get($input, 'description'),
                'is_active'   => 1,
                'role_level'  => $input['role_level'],
                'role_group'  => $input['role_group'],

            ];

            $role = $this->create($param);
        }

        return $role;
    }
}
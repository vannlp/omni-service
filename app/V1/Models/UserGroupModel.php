<?php
/**
 * User: dai.ho
 * Date: 14/05/2020
 * Time: 4:31 PM
 */

namespace App\V1\Models;


use App\Supports\Message;
use App\TM;
use App\UserGroup;
use Illuminate\Support\Arr;

class UserGroupModel extends AbstractModel
{
    public function __construct(UserGroup $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        // Update Default Company
        if (!empty($input['is_default'])) {
            UserGroup::where('company_id', TM::getCurrentCompanyId())->update(['is_default' => null]);
        }

        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            if (!empty($input['is_default']) && $input['is_default'] == 1) {
                UserGroup::model()->where('company_id', TM::getCurrentCompanyId())->update(['is_default'=> null]);
            }
            $userGroup = UserGroup::find($id);
            if (empty($userGroup)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $userGroup->name = array_get($input, 'name', $userGroup->name);
            $userGroup->code = array_get($input, 'code', $userGroup->code);
            $userGroup->description = array_get($input, 'description', null);
            $userGroup->company_id = TM::getCurrentCompanyId();
            $userGroup->is_view = !empty($input['is_view']) ? 1 : 0;
            $userGroup->is_view_app = !empty($input['is_view_app']) ? 1 : 0;
            $userGroup->is_default = !empty($input['is_default']) ? 1 : null;
            $userGroup->updated_at = date("Y-m-d H:i:s", time());
            $userGroup->updated_by = TM::getCurrentUserId();
            $userGroup->save();
        } else {
            if (!empty($input['is_default']) && $input['is_default'] == 1) {
                UserGroup::model()->where('company_id', TM::getCurrentCompanyId())->update(['is_default'=> null]);
            }
            $param = [
                'code'        => $input['code'],
                'name'        => $input['name'],
                'description' => array_get($input, 'description'),
                'company_id'  => TM::getCurrentCompanyId(),
                'is_default'  => !empty($input['is_default']) ? 1 : null,
                'is_view'     => !empty($input['is_view']) ? 1 : 0,
                'is_view_app' => !empty($input['is_view_app']) ? 1 : 0,
                'is_active'   => 1,
            ];
            $userGroup = $this->create($param);
        }

        return $userGroup;
    }
}
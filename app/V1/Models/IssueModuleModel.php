<?php


namespace App\V1\Models;


use App\IssueModule;
use App\Supports\Message;
use App\TM;

class IssueModuleModel extends AbstractModel
{
    public function __construct(IssueModule $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $this->checkUnique(['code' => $input['code']], $id);
            $result = IssueModule::find($id);
            if (empty($result)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $result->name = array_get($input, 'name', $result->name);
            $result->code = array_get($input, 'code', $result->code);
            $result->description = array_get($input, 'description', $result->description);
            $result->company_id = TM::getCurrentCompanyId();
            $result->is_active = array_get($input, 'is_active', $result->is_active);
            $result->updated_at = date("Y-m-d H:i:s", time());
            $result->updated_by = TM::getCurrentUserId();
            $result->save();
        } else {
            $param = [
                'name'        => $input['name'],
                'code'        => $input['code'],
                'company_id'  => TM::getCurrentCompanyId(),
                'description' => array_get($input, 'description', NULL),
                'is_active'   => 1,
            ];

            $result = $this->create($param);
        }

        return $result;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $query->where('company_id', TM::getCurrentCompanyId());
        $issues = new IssueModel();
        $issuesTable = $issues->getTable();
        $moduleCategory = new IssueModuleCategoryModel();
        $moduleCategoryTable = $moduleCategory->getTable();
        $module = new ModuleModel();
        $moduleTable = $module->getTable();
        $user_id = TM::getCurrentUserId();
        $roleCurrent = TM::getCurrentRole();
        if ($roleCurrent == USER_ROLE_ADMIN) {
            if (!empty($input['name'])) {
                $query = $query->where($moduleTable . '.name', 'like', "%{$input['name']}%");
            }
        } else {
            $query = $query
                ->join($moduleCategoryTable, $moduleCategoryTable . '.module_id', '=', $moduleTable . '.id')
                ->join($issuesTable, $issuesTable . '.module_category_id', '=', $moduleCategoryTable . '.id')
                ->where($issuesTable . '.user_id', $user_id)
                ->whereNull($moduleCategoryTable . '.deleted_at')
                ->whereNull($moduleTable . '.deleted_at')
                ->whereNull($issuesTable . '.deleted_at');

            if (!empty($input['name'])) {
                $query = $query->where($moduleTable . '.name', 'like', "%{$input['name']}%");
            }
            $query = $query->select($moduleTable . '.*')->distinct();
        }
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }
}
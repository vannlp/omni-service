<?php


namespace App\V1\Models;


use App\Issue;
use App\IssueModuleCategory;
use App\Supports\Message;
use App\TM;

class IssueModuleCategoryModel extends AbstractModel
{
    public function __construct(IssueModuleCategory $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $this->checkUnique(['code' => $input['code']], $id);
            $categoryTask = IssueModuleCategory::find($id);
            if (empty($categoryTask)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $categoryTask->name = array_get($input, 'name', $categoryTask->name);
            $categoryTask->code = array_get($input, 'code', $categoryTask->code);
            $categoryTask->company_id = TM::getCurrentCompanyId();
            $categoryTask->module_id = array_get($input, 'module_id', $categoryTask->module_id);
            $categoryTask->description = array_get($input, 'description', $categoryTask->description);
            $categoryTask->is_active = array_get($input, 'is_active', $categoryTask->is_active);
            $categoryTask->updated_at = date("Y-m-d H:i:s", time());
            $categoryTask->updated_by = TM::getCurrentUserId();
            $categoryTask->save();
        } else {
            $param = [
                'name'        => $input['name'],
                'code'        => $input['code'],
                'company_id'  => TM::getCurrentCompanyId(),
                'module_id'   => array_get($input, 'module_id', NULL),
                'description' => array_get($input, 'description', NULL),
                'is_active'   => 1,
            ];

            $categoryTask = $this->create($param);
        }

        return $categoryTask;
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
        $module = new IssueModuleModel();
        $moduleTable = $module->getTable();
        $user_id = TM::getCurrentUserId();
        $roleCurrent = TM::getCurrentRole();
        if ($roleCurrent == USER_ROLE_ADMIN) {
            if (!empty($input['code'])) {
                $query = $query->where('code', 'like', "%{$input['code']}%");
            }
            if (!empty($input['name'])) {
                $query = $query->where('name', 'like', "%{$input['name']}%");
            }
            if (!empty($input['module_name'])) {
                $query = $query->whereHas('module', function ($q) use ($input) {
                    $q->where('name', 'like', "%{$input['module_name']}%");
                });
            }
            if (!empty($input['id'])) {
                $query = $query->whereHas('module', function ($q) use ($input) {
                    $q->where('id', 'like', "%{$input['id']}%");
                });
            }
            if (!empty($input['module_id'])) {
                $query = $query->whereHas('module', function ($q) use ($input) {
                    $q->where('id', $input['module_id']);
                });
            }
        } else {
            $moduleCurrent = Issue::join($moduleCategoryTable, $moduleCategoryTable . '.id', '=', $issuesTable . '.module_category_id')
                ->join($moduleTable, $moduleTable . '.id', '=', $moduleCategoryTable . '.module_id')
                ->where($issuesTable . '.user_id', $user_id)
                ->whereNull($moduleCategoryTable . '.deleted_at')
                ->whereNull($moduleTable . '.deleted_at')
                ->whereNull($issuesTable . '.deleted_at');
            if (!empty($input['code'])) {
                $moduleCurrent = $moduleCurrent->where($moduleCategoryTable . '.code', 'like', "%{$input['code']}%");
            }
            if (!empty($input['name'])) {
                $moduleCurrent = $moduleCurrent->where($moduleCategoryTable . '.name', 'like', "%{$input['name']}%");
            }
            if (!empty($input['module_name'])) {
                $moduleCurrent = $moduleCurrent->where($moduleTable . '.name', 'like', "%{$input['module_name']}%");
            }
            if (!empty($input['id'])) {
                $moduleCurrent = $moduleCurrent->where($moduleTable . '.id', $input['id']);
            }
            $moduleCurrent = $moduleCurrent->select($moduleTable . '.*')->get()->pluck('id', 'code')->toArray();
            $query = $query->whereIn($moduleCategoryTable . '.module_id', array_values($moduleCurrent));
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
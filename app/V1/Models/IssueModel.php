<?php


namespace App\V1\Models;


use App\Issue;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Facades\DB;

class IssueModel extends AbstractModel
{
    public function __construct(Issue $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        $relatedIssue = !empty($input['related_issues']) ? implode(",", array_column($input['related_issues'], 'issue_id')) : null;
        if ($id) {
            $task = Issue::find($id);
            if (empty($task)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $task->name = array_get($input, 'name', $task->name);
            $task->description = array_get($input, 'description', $task->description);
            $task->status = array_get($input, 'status', $task->status);
            $task->estimated_time = array_get($input, 'estimated_time', $task->estimated_time);
            $task->module_category_id = array_get($input, 'module_category_id', $task->module_category_id);
            $task->user_id = array_get($input, 'user_id', $task->user_id);
            $task->parent_id = array_get($input, 'parent_id', $task->parent_id);
            $task->progress = array_get($input, 'progress', $task->progress);
            $task->priority = array_get($input, 'priority', $task->priority);
            $task->company_id = TM::getCurrentCompanyId();
            $task->deadline = date("Y-m-d H:i", strtotime(array_get($input, 'deadline', $task->deadline)));
            $task->start_time = date("Y-m-d H:i", strtotime(array_get($input, 'start_time', $task->start_time)));
            //$task->attachment_id = array_get($input, 'attachment_id', $task->attachment_id);
            $task->version = array_get($input, 'version', $task->version);
            $task->file_id = array_get($input, 'file_id', $task->file_id);
            $task->is_active = array_get($input, 'is_active', $task->is_active);
            $task->is_prompt = array_get($input, 'is_prompt', $task->is_prompt);
            $task->related_issues = $relatedIssue ?? null;
            $task->updated_at = date("Y-m-d H:i:s", time());
            $task->updated_by = TM::getCurrentUserId();
            $issueId = $task->id;
            if (!empty($relatedIssue)) {
                $issueModel = Issue::find($issueId);
                $issueParent = $issueModel->related_issues;
                $arrayRelatedIssues = explode(",", $relatedIssue);
                if (!empty($issueParent)) {
                    $arrayIssueRelatedParents = explode(",", $issueParent);
                    $arrayRelatedIssuesDiffs = array_diff($arrayIssueRelatedParents, $arrayRelatedIssues);
                    if (!empty($arrayRelatedIssuesDiffs)) {
                        foreach ($arrayRelatedIssuesDiffs as $arrayRelatedIssuesDiff) {
                            $relatedIssueModel = Issue::find($arrayRelatedIssuesDiff);
                            $issueRelateds = explode(",", $relatedIssueModel->related_issues);
                            if (!empty($issueRelateds)) {
                                foreach ($issueRelateds as $key => $issueRelated) {
                                    if ($issueRelated == $issueId) {
                                        unset($issueRelateds[$key]);
                                    }
                                    $value = implode(",", $issueRelateds);
                                    $relatedIssueModel->related_issues = $value;
                                    $relatedIssueModel->save();
                                }
                            }
                        }
                    }
                }
                $this->updateRelatedIssue($arrayRelatedIssues, $id);
            }
            $task->save();
        } else {
            $param = [
                'name'               => $input['name'],
                'description'        => array_get($input, 'description', NULL),
                'estimated_time'     => array_get($input, 'estimated_time', NULL),
                'status'             => 'NEW',
                'module_category_id' => array_get($input, 'module_category_id'),
                'user_id'            => array_get($input, 'user_id', null),
                'parent_id'          => array_get($input, 'parent_id'),
                'company_id'         => TM::getCurrentCompanyId(),
                'progress'           => array_get($input, 'progress'),
                'priority'           => array_get($input, 'priority'),
                'deadline'           => !empty($input['deadline']) ? date("Y-m-d H:i", strtotime($input['deadline'])) : null,
                'start_time'         => !empty($input['start_time']) ? date("Y-m-d H:i", strtotime($input['start_time'])) : null,
                'version'            => array_get($input, 'version'),
                'file_id'            => array_get($input, 'file_id'),
                'is_prompt'          => array_get($input, 'is_prompt'),
                'related_issues'     => $relatedIssue ?? null,
                'is_active'          => 1,
            ];
            $task = $this->create($param);
            $relatedIssueId = $task->id;
            if (!empty($relatedIssue)) {
                $relatedIssues = explode(',', $relatedIssue);
                $this->updateRelatedIssue($relatedIssues, $relatedIssueId);
            }
        }
        return $task;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $query->where('company_id', TM::getCurrentCompanyId());
        $issues = new IssueModel();
        $issuesTable = $issues->getTable();
        $modules = new IssueModuleModel();
        $modulesTable = $modules->getTable();
        $moduleCategory = new IssueModuleCategoryModel();
        $moduleCategoryTable = $moduleCategory->getTable();
        $users = new UserModel();
        $userTable = $users->getTable();
        $profiles = new ProfileModel();
        $profilesTable = $profiles->getTable();
        $user_id = TM::getCurrentUserId();
        $roleCurrent = TM::getCurrentRole();
        if ($roleCurrent == USER_ROLE_ADMIN) {
            if (!empty($input['module_category_name'])) {
                $query = $query->whereHas('moduleCategory', function ($q) use ($input) {
                    $q->where('name', 'like', "%{$input['module_category_name']}%");
                });
            }
            if (!empty($input['module_category_code'])) {
                $query = $query->whereHas('moduleCategory', function ($q) use ($input) {
                    $q->where('code', 'like', "%{$input['module_category_code']}%");
                });
            }
            if (!empty($input['user_id'])) {
                $query = $query->where($issuesTable . '.user_id', $input['user_id']);
            }
            if (!empty($input['name'])) {
                $query = $query->where($issuesTable . '.name', 'like', "%{$input['name']}%");
            }
            if (!empty($input[$issuesTable . '.is_active'])) {
                $query = $query->where('is_active', 'like', "%{$input['is_active']}%");
            }
            if (!empty($input['status'])) {
                $query = $query->where('status', $input['status']);
            }
            if (!empty($input['priority'])) {
                $query = $query->where('priority', $input['priority']);
            }
            if (!empty($input['full_name'])) {
                $query = $query
                    ->join($userTable, $userTable . '.id', '=', $issuesTable . '.user_id')
                    ->join($profilesTable, $profilesTable . '.user_id', '=', $userTable . '.id')
                    ->where($profilesTable . '.full_name', 'like', "%{$input['full_name']}%")
                    ->whereNull($userTable . '.deleted_by')
                    ->whereNull($userTable . '.deleted_by')
                    ->whereNull($profilesTable . '.deleted_by')
                    ->select($issuesTable . '.*')->distinct();
            }
            if (!empty($input['module_name'])) {
                $query = $query
                    ->join($moduleCategoryTable, $moduleCategoryTable . '.id', '=', $issuesTable . '.module_category_id')
                    ->join($modulesTable, $modulesTable . '.id', '=', $moduleCategoryTable . '.module_id')
                    ->where($modulesTable . '.name', 'like', "%{$input['module_name']}%")
                    ->whereNull($modulesTable . '.deleted_by')
                    ->whereNull($moduleCategoryTable . '.deleted_by')
                    ->whereNull($issuesTable . '.deleted_by')
                    ->select($issuesTable . '.*')->distinct();
            }
            if (!empty($input['module_id'])) {
                $query = $query
                    ->join($moduleCategoryTable, $moduleCategoryTable . '.id', '=', $issuesTable . '.module_category_id')
                    ->join($modulesTable, $modulesTable . '.id', '=', $moduleCategoryTable . '.module_id')
                    ->where($modulesTable . '.id', $input['module_id'])
                    ->whereNull($modulesTable . '.deleted_by')
                    ->whereNull($moduleCategoryTable . '.deleted_by')
                    ->whereNull($issuesTable . '.deleted_by')
                    ->select($issuesTable . '.*')->distinct();
            }
            if (!empty($input['from']) && !empty($input['to'])) {
                $query = $query->where($issuesTable . '.updated_at', '>=', [date("Y-m-d", strtotime($input['from']))])
                    ->where($issuesTable . '.updated_at', '<=', [date("Y-m-d", strtotime($input['to']))]);
            }
            if (!empty($input['from']) && empty($input['to'])) {
                $query = $query->where($issuesTable . '.updated_at', '>=', [date("Y-m-d", strtotime($input['from']))]);
            }
            if (empty($input['from']) && !empty($input['to'])) {
                $query = $query->where($issuesTable . '.updated_at', '<=', [date("Y-m-d", strtotime($input['to']))]);
            }
            $query = $query->orderBY($issuesTable . '.id', 'DESC');
        } else {
            $moduleCurrent = Issue::join($moduleCategoryTable, $moduleCategoryTable . '.id', '=', $issuesTable . '.module_category_id')
                ->join($modulesTable, $modulesTable . '.id', '=', $moduleCategoryTable . '.module_id')
                ->where($issuesTable . '.user_id', 'like', "{$user_id}")
                ->whereNull($moduleCategoryTable . '.deleted_at')
                ->whereNull($modulesTable . '.deleted_at')
                ->whereNull($issuesTable . '.deleted_at');

            if (!empty($input['module_id'])) {
                $moduleCurrent = $moduleCurrent->where($modulesTable . '.id', $input['module_id']);
            }

            if (!empty($input['module_name'])) {
                $moduleCurrent = $moduleCurrent->where($modulesTable . '.name', 'like', "%{$input['module_name']}%");
            }
            if (!empty($input['module_category_name'])) {
                $moduleCurrent = $moduleCurrent->where($moduleCategoryTable . '.name', 'like', "%{$input['module_category_name']}%");
            }
            if (!empty($input['module_category_code'])) {
                $moduleCurrent = $moduleCurrent->where($moduleCategoryTable . '.code', 'like', "%{$input['module_category_code']}%");

            }
            if (!empty($input['user_id'])) {
                $moduleCurrent = $moduleCurrent->where($issuesTable . '.user_id', $input['user_id']);
            }
            if (!empty($input['name'])) {
                $moduleCurrent = $moduleCurrent->where($issuesTable . '.name', 'like', "%{$input['name']}%");
            }
            if (!empty($input['is_active'])) {
                $moduleCurrent = $moduleCurrent->where($issuesTable . 'is_active', 'like', "%{$input['is_active']}%");
            }
            if (!empty($input['status'])) {
                $moduleCurrent = $moduleCurrent->where($issuesTable . '.status', $input['status']);
            }
            if (!empty($input['priority'])) {
                $moduleCurrent = $moduleCurrent->where($issuesTable . '.priority', $input['priority']);
            }
            if (!empty($input['from']) && !empty($input['to'])) {
                $moduleCurrent = $moduleCurrent->where($issuesTable . '.updated_at', '>=', [date("Y-m-d", strtotime($input['from']))])
                    ->where($issuesTable . '.updated_at', '<=', [date("Y-m-d", strtotime($input['to']))]);
            }
            if (!empty($input['from']) && empty($input['to'])) {
                $moduleCurrent = $moduleCurrent->where($issuesTable . '.updated_at', '>=', [date("Y-m-d", strtotime($input['from']))]);
            }
            if (empty($input['from']) && !empty($input['to'])) {
                $moduleCurrent = $moduleCurrent->where($issuesTable . '.updated_at', '<=', [date("Y-m-d", strtotime($input['to']))]);
            }
            $moduleCurrent = $moduleCurrent->select($modulesTable . '.*')->get()->pluck('id', 'code')->toArray();

            if (!empty($input['full_name'])) {
                $query = $query
                    ->join($userTable, $userTable . '.id', '=', $issuesTable . '.user_id')
                    ->join($profilesTable, $profilesTable . '.user_id', '=', $userTable . '.id')
                    ->where($profilesTable . '.full_name', 'like', "%{$input['full_name']}%")
                    ->whereNull($userTable . '.deleted_by')
                    ->whereNull($userTable . '.deleted_by')
                    ->whereNull($profilesTable . '.deleted_by')
                    ->select($issuesTable . '.*')->distinct();
            }

            $query = $query
                ->join($moduleCategoryTable, $moduleCategoryTable . '.id', '=', $issuesTable . '.module_category_id')
                ->join($modulesTable, $modulesTable . '.id', '=', $moduleCategoryTable . '.module_id')
                ->whereIn($modulesTable . '.id', array_values($moduleCurrent))
                ->whereNull($modulesTable . '.deleted_at')
                ->whereNull($moduleCategoryTable . '.deleted_at')
                ->whereNull($issuesTable . '.deleted_at')
                ->select($issuesTable . '.*')->distinct();
            $query = $query->orderBY($issuesTable . '.id', 'DESC');
        }

        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                if (!empty($input['sort']['date'])) {
                    return $query->get();
                } else {
                    return $query->paginate($limit);
                }
            }
        }
    }

    public
    function searchMine($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $idMine = TM::getCurrentUserId();
        $issues = new IssueModel();
        $issuesTable = $issues->getTable();
        $modules = new IssueModuleModel();
        $modulesTable = $modules->getTable();
        $moduleCategory = new IssueModuleCategoryModel();
        $moduleCategoryTable = $moduleCategory->getTable();
        $users = new UserModel();
        $userTable = $users->getTable();
        $profiles = new ProfileModel();
        $profilesTable = $profiles->getTable();
        if (!empty($input['module_category_name'])) {
            $query = $query->whereHas('moduleCategory', function ($q) use ($input) {
                $q->where('name', 'like', "%{$input['module_category_name']}%");
            });
        }
        if (!empty($input['module_category_code'])) {
            $query = $query->whereHas('moduleCategory', function ($q) use ($input) {
                $q->where('code', 'like', "%{$input['module_category_code']}%");
            });
        }
        if (!empty($input['name'])) {
            $query = $query->where('name', 'like', "%{$input['name']}%");
        }
        if (!empty($input['is_active'])) {
            $query = $query->where('is_active', 'like', "%{$input['is_active']}%");
        }
        if (!empty($input['status'])) {
            $query = $query->where('status', $input['status']);
        }
        if (!empty($input['priority'])) {
            $query = $query->where('priority', $input['priority']);
        }
        if (!empty($input['full_name'])) {
            $query = $query
                ->join($userTable, $userTable . '.id', '=', $issuesTable . '.user_id')
                ->join($profilesTable, $profilesTable . '.user_id', '=', $userTable . '.id')
                ->where($profilesTable . '.full_name', 'like', "%{$input['full_name']}%")
                ->whereNull($userTable . '.deleted_by')
                ->whereNull($userTable . '.deleted_by')
                ->whereNull($profilesTable . '.deleted_by')
                ->select($issuesTable . '.*')->distinct();
        }
        if (!empty($input['module_name'])) {
            $query = $query
                ->join($moduleCategoryTable, $moduleCategoryTable . '.id', '=', $issuesTable . '.module_category_id')
                ->join($modulesTable, $modulesTable . '.id', '=', $moduleCategoryTable . '.module_id')
                ->where($modulesTable . '.name', 'like', "%{$input['module_name']}%")
                ->whereNull($modulesTable . '.deleted_by')
                ->whereNull($moduleCategoryTable . '.deleted_by')
                ->whereNull($issuesTable . '.deleted_by')
                ->select($issuesTable . '.*')->distinct();
        }
        $query = $query->where('user_id', $idMine)->orderBY('id', 'DESC');
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                if (!empty($input['sort']['date'])) {
                    return $query->get();
                } else {
                    return $query->paginate($limit);
                }
            }
        }
    }

    public
    function searchUserDaiLy($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $issue = new IssueModel();
        $issueTable = $issue->getTable();
        $query = Issue::model()->select([
            $issueTable . '.user_id',
            (DB::raw("COUNT(id) as issueToday")),
            (DB::raw("SUM(estimated_time) as workTime")),
        ]);

        if (!empty($input['from']) && !empty($input['to'])) {
            $query = $query->whereBetween('updated_at',
                [date("Y-m-d", strtotime($input['from'])), date("Y-m-d", strtotime($input['to']))]);
        } else {
            $query = $query->whereDate('updated_at', date("Y-m-d", time()));
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

    private function updateRelatedIssue($array, $id)
    {
        foreach ($array as $value) {
            $issueModel = Issue::find($value);
            if (!empty($issueModel->related_issues)) {
                $arrayIssue = explode(",", $issueModel->related_issues);
                $arrayIssue[] = $id;
                $issueModel->related_issues = implode(",", array_unique($arrayIssue));
                $issueModel->save();
            }
            if (empty($issueModel->related_issues)) {
                $issueModel->related_issues = $id;
                $issueModel->save();
            }
        }
    }
}
<?php


namespace App\V1\Transformers\Issue;


use App\Issue;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class IssueTransformer extends TransformerAbstract
{
    /**
     * @param Issue $issue
     * @return array
     * @throws \Exception
     */
    public function transform(Issue $issue)
    {
        try {

            $folder_path = object_get($issue, 'file.folder.folder_path');
            if (!empty($folder_path)) {
                $folder_path = str_replace("/", ",", $folder_path);
            } else {
                $folder_path = "uploads";
            }
            $folder_path = url('/v0') . "/img/" . $folder_path;
            $file_name = object_get($issue, 'file.file_name');
            return [
                'id'                        => $issue->id,
                'name'                      => $issue->name,
                'description'               => $issue->description,
                'status'                    => $issue->status,
                'estimated_time'            => $issue->estimated_time,
                'user_id'                   => object_get($issue, 'user_id', null),
                'full_name'                 => object_get($issue, 'user.profile.full_name', null),
                'parent_id'                 => $issue->parent_id,
                'company_id'                => $issue->company_id,
                'progress'                  => $issue->progress,
                'priority'                  => $issue->priority,
                'module_category_id'        => $issue->module_category_id,
                'module_category_code'      => object_get($issue, 'moduleCategory.code'),
                'module_category_name'      => object_get($issue, 'moduleCategory.name'),
                'create_by_module_category' => object_get($issue, 'moduleCategory.user.profile.full_name'),
                'module_id'                 => object_get($issue, 'moduleCategory.module_id'),
                'module_code'               => object_get($issue, 'moduleCategory.module.code'),
                'module_name'               => object_get($issue, 'moduleCategory.module.name'),
                'create_by_module'          => object_get($issue, 'module.moduleCategory.user.profile.full_name'),
                'version'                   => $issue->version,
                'file_id'                   => $issue->file_id,
                'file'                      => !empty($file_name) ? $folder_path . ',' . $file_name : null,
                'title'                     => object_get($issue, 'file.title'),
                'deadline'                  => !empty($issue->deadline) ? date('d-m-Y H:i',
                    strtotime($issue->deadline)) : null,
                'start_time'                => !empty($issue->start_time) ? date('d-m-Y H:i',
                    strtotime($issue->start_time)) : null,
                'related_issues'            => !empty($issue->related_issues) ? $this->getRelatedIssue($issue->related_issues) : [],
                'is_active'                 => $issue->is_active,
                'created_at'                => date('d/m/Y H:i', strtotime($issue->created_at)),
                'created_by'                => object_get($issue, 'created_By.profile.full_name'),
                'updated_at'                => !empty($issue->updated_at) ? date('d/m/Y H:i',
                    strtotime($issue->updated_at)) : null,
                'updated_by'                => object_get($issue, 'updated_By.profile.full_name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }

    private function getRelatedIssue($ids)
    {
        $dataRelateIssues = [];
        if (!empty($ids)) {
            $exp = explode(',', $ids);
            $issues = Issue::model()->whereIn('id', $exp)->get();
            foreach ($issues as $issue) {
                $dataRelateIssues[] = [
                    "issue_id"        => $issue->id,
                    "issue_name"      => $issue->name,
                    "issue_full_name" => object_get($issue, 'user.profile.full_name', null),
                    "issue_status"    => ISSUE_STATUS_NAME[$issue->status],
                    "issue_progress"  => !empty($issue->progress) ? $issue->progress : 0 . "%"
                ];
            }
        }
        return $dataRelateIssues;
    }
}
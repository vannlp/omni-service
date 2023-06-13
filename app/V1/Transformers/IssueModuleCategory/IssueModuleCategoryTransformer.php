<?php


namespace App\V1\Transformers\IssueModuleCategory;


use App\IssueModuleCategory;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class IssueModuleCategoryTransformer extends TransformerAbstract
{
    /**
     * @param IssueModuleCategory $moduleCategory
     * @return array
     * @throws \Exception
     */

    public function transform(IssueModuleCategory $moduleCategory)
    {
        try {
            return [
                'id'          => $moduleCategory->id,
                'name'        => $moduleCategory->name,
                'code'        => $moduleCategory->code,
                'description' => $moduleCategory->description,
                'module_id'   => $moduleCategory->module_id,
                'company_id'  => $moduleCategory->company_id,
                'module_code' => object_get($moduleCategory, 'module.code'),
                'module_name' => object_get($moduleCategory, 'module.name'),
                'is_active'   => $moduleCategory->is_active,
                'created_at'  => date('d/m/Y H:i', strtotime($moduleCategory->created_at)),
                'updated_at'  => !empty($moduleCategory->updated_at) ? date('d/m/Y H:i',
                    strtotime($moduleCategory->updated_at)) : null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
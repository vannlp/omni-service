<?php


namespace App\V1\Transformers\IssueModule;


use App\IssueModule;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class IssueModuleTransformer extends TransformerAbstract
{
    /**
     * @param IssueModule $module
     * @return array
     * @throws \Exception
     */

    public function transform(IssueModule $module)
    {
        try {
            return [
                'id'          => $module->id,
                'name'        => $module->name,
                'code'        => $module->code,
                'description' => $module->description,
                'company_id'  => $module->company_id,
                'is_active'   => $module->is_active,
                'created_at'  => date('d/m/Y H:i', strtotime($module->created_at)),
                'updated_at'  => !empty($module->updated_at) ? date('d/m/Y H:i',
                    strtotime($module->updated_at)) : null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
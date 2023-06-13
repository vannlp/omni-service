<?php
/**
 * User: dai.ho
 * Date: 14/05/2020
 * Time: 4:33 PM
 */

namespace App\V1\Transformers\UserGroup;


use App\Supports\TM_Error;
use App\UserGroup;
use League\Fractal\TransformerAbstract;

class UserGroupTransformer extends TransformerAbstract
{
    public function transform(UserGroup $userGroup)
    {
        try {
            return [
                'id'          => $userGroup->id,
                'code'        => $userGroup->code,
                'name'        => $userGroup->name,
                'description' => $userGroup->description,
                'is_default'  => $userGroup->is_default,
                'company_id'  => $userGroup->company_id,
                'is_view'     => $userGroup->is_view,
                'is_view_app' => $userGroup->is_view_app,
                'is_active'   => $userGroup->is_active,
                'created_at'  => date('d-m-Y', strtotime($userGroup->created_at)),
                'updated_at'  => date('d-m-Y', strtotime($userGroup->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\V1\Transformers\PromotionProgram;

use App\PromotionProgram;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class PromotionProgramClientTransformer extends TransformerAbstract
{
    public function transform(PromotionProgram $model)
    {
        try {
            $file_code = object_get($model, 'thumbnail.code');
            return [
                'id'               => $model->id,
                'code'             => $model->code,
                'name'             => $model->name,
                'view'             => $model->view,
                'description'      => $model->description,
                'thumbnail_id'     => $model->thumbnail_id,
                // 'thumbnail'          => $model->thumbnail ? $model->thumbnail->url : null,
                'thumbnail'        => !empty($file_code) ? env('UPLOAD_URL') . '/file/' . $file_code : null,
                'general_settings' => $model->general_settings,
                'status'           => $model->status,
                'stack_able'       => $model->stack_able,
                'type'             => $model->type,
                // 'multiply'           => $model->multiply,
                // 'sort_order'         => $model->sort_order,
                'start_date'       => date('d-m-Y', strtotime($model->start_date)),
                'end_date'         => date('d-m-Y', strtotime($model->end_date)),
                // 'total_user'         => $model->total_user,
                // 'total_use_customer' => $model->total_use_customer,
                'promotion_type'   => $model->promotion_type,
                'coupon_code'      => $model->coupon_code,
                'need_login'       => $model->need_login,
                'default_store'    => $model->default_store ? \GuzzleHttp\json_decode($model->default_store) : '',
                // 'group_customer'     => $model->group_customer ? \GuzzleHttp\json_decode($model->group_customer) : '',
                // 'actions'            => $model->actions,
                'conditions'         => $model->conditions,
                'company_id'       => $model->company_id,
                'company_code'     => object_get($model, 'company.code'),
                'company_name'     => object_get($model, 'company.name'),
                'actions'          => $model->actions,
                'created_at'       => date('d-m-Y', strtotime($model->created_at)),
                'updated_at'       => date('d-m-Y', strtotime($model->updated_at)),
            ];
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

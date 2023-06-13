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

class PromotionProgramTransformer extends TransformerAbstract
{
    public function transform(PromotionProgram $model)
    {
        try {
            return [
                'id'                 => $model->id,
                'code'               => $model->code,
                'name'               => $model->name,
                'type'               => $model->type,
                'description'        => $model->description,
                'tags'               => $model->tags ? \GuzzleHttp\json_decode($model->tags) : '',
                'thumbnail_id'       => $model->thumbnail_id,
                'thumbnail'          => !empty($model->thumbnail->code) ? env('GET_FILE_URL').$model->thumbnail->code : null,
                'iframe_image_id'    => $model->iframe_image_id,
                'iframe_image'       => !empty($model->iframeImage->code) ? env('GET_FILE_URL').$model->iframeImage->code : null,
                'general_settings'   => $model->general_settings,
                'status'             => $model->status,
                'stack_able'         => $model->stack_able,
                'multiply'           => $model->multiply,
                'sort_order'         => $model->sort_order,
                'start_date'         => $model->start_date,
                'end_date'           => $model->end_date,
                'total_user'         => $model->total_user,
                'total_use_customer' => $model->total_use_customer,
                'promotion_type'     => $model->promotion_type,
                'is_exchange'        => $model->is_exchange,
                'coupon_code'        => $model->coupon_code,
                'need_login'         => $model->need_login,
                'default_store'      => $model->default_store ? \GuzzleHttp\json_decode($model->default_store) : '',
                'group_customer'     => $model->group_customer ? \GuzzleHttp\json_decode($model->group_customer) : '',
                'area_ids'           => $model->area_ids ? \GuzzleHttp\json_decode($model->area_ids) : '',
                'area'               => $model->area ? \GuzzleHttp\json_decode($model->area) : '',
                'region'             => $model->region ? \GuzzleHttp\json_decode($model->region) : '',
                'actions'            => $model->actions,
                'conditions'         => $model->conditions,
                'company_id'         => $model->company_id,
                'company_code'       => object_get($model, 'company.code'),
                'company_name'       => object_get($model, 'company.name'),
                'created_at'         => date('d-m-Y', strtotime($model->created_at)),
                'updated_at'         => date('d-m-Y', strtotime($model->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

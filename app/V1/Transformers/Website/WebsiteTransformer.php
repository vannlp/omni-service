<?php


namespace App\V1\Transformers\Website;


use App\Supports\TM_Error;
use App\Website;
use League\Fractal\TransformerAbstract;

class WebsiteTransformer extends TransformerAbstract
{
    public function transform(Website $model)
    {
        try {
            return [
                'id'                 => $model->id,
                'name'               => $model->name,
                'domain'             => $model->domain,
                'logo'               => $model->logo,
                'theme_data'         => object_get($model, 'theme_data', null),
                'favico'             => $model->favico,
                'description'        => $model->description,
                'keyword'            => $model->keyword,
                'blog_id'            => object_get($model, 'blog_id', null),
                'company_id'         => $model->company_id,
                'blog_name'          => object_get($model, 'getBlog.name'),
                'store_id'           => $model->store_id,
                'store_code'         => object_get($model, 'getStore.code'),
                'store_name'         => object_get($model, 'getStore.name'),
                'status'             => $model->status,
                'facebook_id'        => $model->status,
                'google_analytic_id' => $model->google_analytic_id,
                'facebook_analytics' => $model->facebook_analytics,
                'google_analytics'   => $model->google_analytics,
                'is_active'          => $model->is_active,
                'created_at'         => date('d-m-Y', strtotime($model->created_at)),
                'updated_at'         => date('d-m-Y', strtotime($model->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
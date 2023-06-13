<?php


namespace App\V1\Transformers\WebsiteTheme;


use App\Supports\TM_Error;
use App\WebsiteTheme;
use League\Fractal\TransformerAbstract;

class WebsiteThemTransformer extends TransformerAbstract
{
    public function transform(WebsiteTheme $model)
    {
        try {
            return [
                'id'             => $model->id,
                'name'           => $model->name,
                'theme_data'     => $model->theme_data,
                'screenshot'     => object_get($model, 'screenshot', null),
                'description'    => object_get($model, 'description', null),
                'theme_color'    => object_get($model, 'theme_color', null),
                'theme_style'    => object_get($model, 'theme_style', null),
                'theme_category' => object_get($model, 'theme_category', null),
                'is_active'      => $model->is_active,
                'created_at'     => date('d-m-Y', strtotime($model->created_at)),
                'updated_at'     => date('d-m-Y', strtotime($model->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
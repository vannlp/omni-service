<?php


namespace App\V1\Transformers\AppMenu;


use App\AppMenu;
use App\File;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class AppMenuTransformers extends TransformerAbstract
{
    public function transform(AppMenu $app_menu)
    {
        try {
            return [
                'id'         => $app_menu->id,
                'code'       => $app_menu->code,
                'name'       => $app_menu->name,
                'data'       => $app_menu->data,
                'store_id'   => $app_menu->store_id,
                'created_at' => date('d-m-Y', strtotime($app_menu->created_at)),
                'created_by' => object_get($app_menu, 'createdBy.profile.full_name'),
                'updated_at' => date('d-m-Y', strtotime($app_menu->updated_at)),
                'updated_by' => object_get($app_menu, 'updatedBy.profile.full_name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
<?php
/**
 * User: kpistech2
 * Date: 2020-07-04
 * Time: 00:49
 */

namespace App\V1\Transformers\Setting;


use App\Setting;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class SettingTransformer extends TransformerAbstract
{
    public function transform(Setting $item)
    {
        try {
            return [
                'id'          => $item->id,
                'code'        => $item->code,
                'slug'        => $item->slug,
                'name'        => $item->name,
                'value'       => $item->value,
                'description' => $item->description,
                'publish'     => $item->publish,
                'type'        => $item->type,

                'data'       => json_decode($item->data, true),
                'categories' => json_decode($item->categories, true) ?? [],
                'data_cke'   => $item->data_cke,
                'store_id'   => $item->store_id,
                'store_code' => object_get($item, 'store.code', null),
                'store_name' => object_get($item, 'store.name', null),

                'company_id'   => $item->company_id,
                'company_code' => object_get($item, 'company.code', null),
                'company_name' => object_get($item, 'company.name', null),

                'is_active'  => $item->is_active,
                'updated_at' => !empty($item->updated_at) ? date('d-m-Y', strtotime($item->updated_at)) : null,
            ];
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

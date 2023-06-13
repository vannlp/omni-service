<?php
/**
 * User: kpistech2
 * Date: 2019-11-03
 * Time: 15:12
 */

namespace App\V1\Transformers\Store;


use App\Store;
use App\Warehouse;
use App\WarehouseDetail;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class StoreTransformer extends TransformerAbstract
{
    public function transform(Store $store)
    {
        $products = [];
        $warehouseDetail = WarehouseDetail::where('company_id',$store->company_id)->orderBy('quantity','DESC')->first();
        if (!empty($store->products)) {
            foreach ($store->products as $product) {
                $products[] = [
                    'id'         => $product->id,
                    'code'       => $product->code,
                    'name'       => $product->name,
                    'price'      => $product->price,
                    'price_down' => $product->price_down,
                    'down_rate'  => $product->price > 0 ? $product->price_down * 100 / $product->price : 0,
                    'down_from'  => $product->down_from,
                    'down_to'    => $product->down_to,
                ];
            }
        }
        try {
            return [
                'id'            => $store->id,
                'code'          => $store->code,
                'name'          => $store->name,
                'lat'           => $store->lat,
                'long'          => $store->long,
                'description'   => $store->description,
                'address'       => $store->address,
                'contact_phone' => $store->contact_phone,
                'email'         => $store->email,
                'email_notify'  => $store->email_notify,
                'token'         => $store->token,
                'products'      => $products,

                'company_id'   => $store->company_id,
                'company_code' => object_get($store, 'company.code'),
                'company_name' => object_get($store, 'company.name'),

                'batch_id'   => object_get($warehouseDetail, 'batch_id',null),
                'batch_code' => object_get($warehouseDetail, 'batch_code',null),
                'batch_name' => object_get($warehouseDetail, 'batch_name',null),

                'warehouse_id'   => $store->warehouse_id,
                'warehouse_code' => object_get($store, 'warehouse.code'),
                'warehouse_name' => object_get($store, 'warehouse.name'),

                'city_code' => $store->city_code,
                'city_type' => object_get($store, 'city.type'),
                'city_name' => object_get($store, 'city.name'),

                'district_code' => $store->district_code,
                'district_type' => object_get($store, 'district.type'),
                'district_name' => object_get($store, 'district.name'),

                'ward_code' => $store->ward_code,
                'ward_type' => object_get($store, 'ward.type'),
                'ward_name' => object_get($store, 'ward.name'),

                'is_active'  => $store->is_active,
                'created_at' => date('d-m-Y', strtotime($store->created_at)),
                'updated_at' => date('d-m-Y', strtotime($store->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

<?php


namespace App\V1\Transformers\Store;


use App\Store;
use App\Supports\TM_Error;
use App\TM;
use App\Warehouse;
use App\WarehouseDetail;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class MyStoreTransformer extends TransformerAbstract
{
    public function transform(Store $store)
    {
        $products = [];
        $warehouse = Warehouse::model()->where([
            'company_id' => TM::getCurrentCompanyId(),
            'store_id' => TM::getCurrentStoreId()
        ])->first();
        if (!empty($warehouse)) {
            $warehouseDetail = WarehouseDetail::model()->where('warehouse_id', $warehouse->id)->first();
        }
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

                'batch_id'   => Arr::get($warehouseDetail, 'batch_id',null),
                'batch_code' => Arr::get($warehouseDetail, 'batch_code',null),
                'batch_name' => Arr::get($warehouseDetail, 'batch_name',null),

                'warehouse_id'   => $warehouse->id ?? null,
                'warehouse_code' => object_get($warehouse, 'code'),
                'warehouse_name' => object_get($warehouse, 'name'),

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
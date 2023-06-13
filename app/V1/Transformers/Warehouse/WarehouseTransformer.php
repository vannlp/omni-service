<?php


namespace App\V1\Transformers\Warehouse;


use App\CustomerGroup;
use App\Supports\TM_Error;
use App\V1\Models\WarehouseDetailModel;
use App\Warehouse;
use App\WarehouseDetail;
use League\Fractal\TransformerAbstract;

class WarehouseTransformer extends TransformerAbstract
{
    public function transform(Warehouse $warehouse)
    {
        $warehouseDetails = object_get($warehouse, 'warehouseDetail');
        try {
            return [
                'id'                => $warehouse->id,
                'code'              => $warehouse->code,
                'name'              => $warehouse->name,
                'status'            => !empty($warehouseDetails->toArray()) ? STATUS_WAREHOUSE_DETAILS[1] : STATUS_WAREHOUSE_DETAILS[0],
                'address'           => $warehouse->address,
                'description'       => $warehouse->description,
                'company_id'        => $warehouse->company_id,
                'is_active'         => $warehouse->is_active,
                'updated_at'        => !empty($warehouse->updated_at) ? date('d-m-Y',
                    strtotime($warehouse->updated_at)) : null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
<?php


namespace App\V1\Transformers\Warehouse;


use App\Supports\TM_Error;
use App\WarehouseDetail;
use League\Fractal\TransformerAbstract;

class WarehouseDetailTransformer extends TransformerAbstract
{
    public function transform(WarehouseDetail $warehouseDetail)
    {
        try {
            return [
                "id"             => $warehouseDetail->id,
                "product_id"     => $warehouseDetail->product_id,
                "product_code"   => $warehouseDetail->product_code,
                "product_name"   => $warehouseDetail->product_name,
                "warehouse_id"   => $warehouseDetail->warehouse_id,
                "warehouse_code" => $warehouseDetail->warehouse_code,
                "warehouse_name" => $warehouseDetail->warehouse_name,
                "unit_id"        => $warehouseDetail->unit_id,
                "unit_code"      => $warehouseDetail->unit_code,
                "unit_name"      => $warehouseDetail->unit_name,
                "batch_id"       => $warehouseDetail->batch_id,
                "batch_code"     => $warehouseDetail->batch_code,
                "batch_name"     => $warehouseDetail->batch_name,
                "company_id"     => $warehouseDetail->company_id,
                "product_type"   => object_get($warehouseDetail, 'product_type', null),
                "quantity"       => $warehouseDetail->quantity,
                "exp"            => !empty($warehouseDetail->exp) ? date("Y-m-d", strtotime($warehouseDetail->exp)) : null,
                "price"          => $warehouseDetail->price,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
<?php


namespace App\V1\Transformers\Inventory;


use App\Inventory;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class InventoryTransformers extends TransformerAbstract
{
    public function transform(Inventory $invoice)
    {
        $details = $invoice->details;
        $invoiceDetails = [];
        $warehouseId = null;
        foreach ($details as $detail) {
            $warehouseId = $detail->warehouse_id;
            $invoiceDetails[] = [
                "id"             => $detail->id,
                "product_id"     => $detail->product_id,
                "product_code"   => object_get($detail, 'product_code', null),
                "product_name"   => object_get($detail, 'product_name', null),
                "inventory_id"   => $detail->inventory_id,
                "quantity"       => $detail->quantity,
                "exp"            => !empty($detail->exp) ? date('d-m-Y', strtotime($detail->exp)) : null,
                "price"          => !empty($detail->price) ? $detail->price : 0,
                "unit_id"        => $detail->unit_id,
                "unit_code"      => object_get($detail, 'unit_code', null),
                "unit_name"      => object_get($detail, 'unit_name', null),
                "batch_id"       => $detail->batch_id,
                "batch_code"     => object_get($detail, 'batch_code', null),
                "batch_name"     => object_get($detail, 'batch_name', null),
                "warehouse_id"   => $detail->warehouse_id,
                "warehouse_code" => object_get($detail, 'warehouse_code', null),
                "warehouse_name" => object_get($detail, 'warehouse_name', null),
            ];
        }
        try {
            return [
                'id'           => $invoice->id,
                'code'         => $invoice->code,
                "transport"    => object_get($invoice, 'transport', null),
                "user_id"      => $invoice->user_id,
                "full_name"    => object_get($invoice, 'user.profile.full_name', null),
                "company_id"   => $invoice->company_id,
                "date"         => date('d-m-Y', strtotime($invoice->date)),
                "status"       => $invoice->status,
                "description"  => object_get($invoice, 'description', null),
                "type"         => $invoice->type,
                "warehouse_id" => $warehouseId,
                "details"      => $invoiceDetails,
                "providers"    => object_get($invoice, 'providers', null),
                'created_at'   => date('d-m-Y', strtotime($invoice->created_at)),
                'updated_at'   => date('d-m-Y', strtotime($invoice->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
<?php


namespace App\V1\Transformers\Inventory;


use App\InventoryDetail;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class InventoryDetailTransformers extends TransformerAbstract
{
    public function transform(InventoryDetail $invoiceDetail)
    {
        try {
            return [
                'id'                => $invoiceDetail->id,
                'type'              => object_get($invoiceDetail, 'invoice.type'),
                'batch_id'          => $invoiceDetail->batch_id,
                'batch_code'        => object_get($invoiceDetail, 'batchInvoice.code'),
                'batch_name'        => object_get($invoiceDetail, 'batchInvoice.name'),
                'invoice_id'        => $invoiceDetail->invoice_id,
                'product_id'        => $invoiceDetail->product_id,
                'product_code'      => object_get($invoiceDetail, 'productInvoice.code'),
                'product_name'      => object_get($invoiceDetail, 'productInvoice.name'),
                'quantity'          => $invoiceDetail->quantity,
                'unit_id'           => $invoiceDetail->unit_id,
                'unit_code'         => object_get($invoiceDetail, 'unitInvoice.code'),
                'unit_name'         => object_get($invoiceDetail, 'unitInvoice.name'),
                'warehouse_id'      => $invoiceDetail->warehouse_id,
                'warehouse_code'    => object_get($invoiceDetail, 'warehouseInvoice.code'),
                'warehouse_name'    => object_get($invoiceDetail, 'warehouseInvoice.name'),
                'created_at'        => date('d-m-Y H:i:s', strtotime($invoiceDetail->created_at)),
                'created_by'        => object_get($invoiceDetail, 'createdBy.profile.full_name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
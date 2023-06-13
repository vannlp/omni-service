<?php


namespace App\V1\Transformers\OrderStatus;


use App\OrderStatus;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class OrderStatusTransformer extends TransformerAbstract
{
    public function transform(OrderStatus $orderStatus)
    {
        try {
            return [
                'id'           => $orderStatus->id,
                'code'         => $orderStatus->code,
                'name'         => $orderStatus->name,
                'description'  => $orderStatus->description,
                'company_id'   => $orderStatus->company_id,
                'company_code' => object_get($orderStatus, 'getCompany.code', null),
                'company_name' => object_get($orderStatus, 'getCompany.name', null),
                'order'        => $orderStatus->order,
                'status_for'   => $orderStatus->status_for,
                'deleted'      => $orderStatus->deleted,
                'is_active'    => $orderStatus->is_active,
                'created_at'   => date('d-m-Y', strtotime($orderStatus->created_at)),
                'created_by'   => object_get($orderStatus, "createdBy.profile.full_name"),
                'updated_at'   => date('d-m-Y', strtotime($orderStatus->updated_at)),
                'updated_by'   => object_get($orderStatus, "updatedBy.profile.full_name"),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
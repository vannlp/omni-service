<?php


namespace App\V1\Transformers\Notify;


use App\Notify;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class NotifyTransformer extends TransformerAbstract
{
    public function transform(Notify $notify)
    {
        try {
            return [
                'id'                   => $notify->id,
                'title'                => $notify->title,
                'body'                 => $notify->body,
                'type'                 => $notify->type,
                'target_id'            => $notify->target_id,
                'product_search_query' => $notify->product_search_query,
                'notify_for'           => $notify->notify_for,
                'delivery_date'        => !empty($notify->delivery_date) ? date("d-m-Y",
                    strtotime($notify->delivery_date)) : null,
                'frequency'            => $notify->frequency,
                'company_id'           => $notify->company_id,
                'company_code'         => object_get($notify, 'company.code'),
                'company_name'         => object_get($notify, 'company.name'),
                'user_id'              => $notify->user_id,
                'is_active'            => $notify->is_active,
                'created_at'           => date('d-m-Y', strtotime($notify->created_at)),
                'updated_at'           => date('d-m-Y', strtotime($notify->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
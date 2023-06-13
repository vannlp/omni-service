<?php


namespace App\V1\Transformers\PaymentHistory;


use App\PaymentHistory;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class PaymentHistoryTransformer extends TransformerAbstract
{
    public function transform(PaymentHistory $paymentHistory)
    {
        try {
            return [
                'id'                 => $paymentHistory->id,
                'transaction_id'     => $paymentHistory->transaction_id,
                'date'               => date('d-m-Y', strtotime($paymentHistory->date)),
                'type'               => $paymentHistory->type,
                'type_description'   => PAYMENT_TYPE_NAME[$paymentHistory->type] ?? null,
                'method'             => $paymentHistory->method,
                'method_description' => PAYMENT_METHOD_NAME[$paymentHistory->method] ?? null,
                'status'             => $paymentHistory->status,
                'status_description' => PAYMENT_STATUS_NAME[$paymentHistory->status] ?? null,
                'content'            => $paymentHistory->content,
                'total_pay'          => $paymentHistory->total_pay,
                'balance'            => $paymentHistory->balance,
                'user_id'            => $paymentHistory->user_id,
                'data'               => $paymentHistory->data,
                'note'               => $paymentHistory->note,
                'is_active'          => $paymentHistory->is_active,
                'created_at'         => date('d-m-Y', strtotime($paymentHistory->created_at)),
                'created_by'         => object_get($paymentHistory, "createdBy.profile.full_name"),
                'updated_at'         => date('d-m-Y', strtotime($paymentHistory->updated_at)),
                'updated_by'         => object_get($paymentHistory, "updatedBy.profile.full_name"),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
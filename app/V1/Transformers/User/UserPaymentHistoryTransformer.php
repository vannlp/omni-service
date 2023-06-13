<?php


namespace App\V1\Transformers\User;


use App\PaymentHistory;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class UserPaymentHistoryTransformer extends TransformerAbstract
{
    public function transform(PaymentHistory $paymentHistory)
    {
        try {
            return [
                'id'                 => $paymentHistory->id,
                'transaction_id'     => $paymentHistory->transaction_id,
                'title'              => PAYMENT_TYPE_NAME[$paymentHistory->type],
                'date'               => date('d/m/Y; H:i', strtotime($paymentHistory->date)),
                'type'               => $paymentHistory->type,
                'type_description'   => PAYMENT_TYPE_NAME[$paymentHistory->type],
                'method'             => $paymentHistory->method,
                'method_description' => PAYMENT_METHOD_NAME[$paymentHistory->method],
                'status'             => $paymentHistory->status,
                'status_description' => PAYMENT_STATUS_NAME[$paymentHistory->status],
                'content'            => $paymentHistory->content,
                'total_pay'          => $paymentHistory->total_pay,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
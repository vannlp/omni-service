<?php
/**
 * User: dai.ho
 * Date: 10/14/2019
 * Time: 10:37 AM
 */

namespace App\V1\Transformers\User;


use App\Supports\TM_Error;
use App\UserStatusOrder;
use League\Fractal\TransformerAbstract;

class UserStatusOrderTransformer extends TransformerAbstract
{
    public function transform(UserStatusOrder $userStatusOrder)
    {
        try {

            return [
                'user_id'      => $userStatusOrder->user_id,
                'user_name'    => object_get($userStatusOrder, 'user.profile.full_name'),
                'qty_canceled' => $userStatusOrder->where('status', ORDER_STATUS_CANCELED)->count() ?? 0,
                'qty_received' => $userStatusOrder->where('status', ORDER_STATUS_RECEIVED)->count() ?? 0,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
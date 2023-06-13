<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App\V1\Transformers\User;

use App\TM;
use App\User;
use App\Order;
use App\Supports\DataUser;
use App\Supports\TM_Error;
use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;

/**
 * Class UserTransformer
 *
 * @package App\V1\Transformers
 */
class UserGetListTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        try {
            $total = Order::model()->where(['customer_id' => $user->id, 'status'=> 'COMPLETED'])->sum('total_price');
            
            if(!empty(TM::info())){
                if($user->role->code == USER_ROLE_LEADER){
                    $totalSellerOrder = Order::model()->where(['leader_id' => $user->id, 'status'=> 'NEW','status_crm'=>ORDER_STATUS_CRM_PENDING])->count('id');
                }
                if($user->role->code == USER_ROLE_SELLER){
                    $totalSellerOrder = Order::model()->where(['seller_id' => $user->id, 'status'=> 'NEW','status_crm'=>ORDER_STATUS_CRM_PENDING])->count('id');
                }
                if(TM::info()['role_code'] == USER_ROLE_MONITOR || TM::info()['role_code'] == USER_ROLE_ADMIN){
                    $totalSellerOrderMonitor = Order::model()->where(['status'=> 'NEW','status_crm'=>ORDER_STATUS_CRM_PENDING])->whereNull('leader_id')->count('id');
                }
            }
            $totalSellerOrderAll = Order::model()->where(['status'=> 'NEW','status_crm'=>ORDER_STATUS_CRM_PENDING])->whereNull('seller_id');
            // if(TM::info()['role_code'] == USER_ROLE_LEADER){
            if(!empty(TM::info()) && TM::info()['role_code'] == USER_ROLE_LEADER){
                $totalSellerOrderAll= $totalSellerOrderAll->where('leader_id',TM::info()['id']);
            }
            if(!empty(TM::info()) && TM::info()['role_code'] != USER_ROLE_LEADER){
                $totalSellerOrderAll= $totalSellerOrderAll->whereNull('leader_id');
            }
            $totalSellerOrderAll = $totalSellerOrderAll->count('id');
            return [
                'id'               => $user->id,
                'code'             => $user->code,
                'phone'            => $user->phone,
                'email'            => $user->email,
                'full_name'        => object_get($user, "profile.full_name", null),
                'address'          => object_get($user, "profile.address", null),
                'city_code'        => object_get($user, "profile.city_code", null),
                'city_name'        => object_get($user, "profile.city.full_name", null),
                'district_code'    => object_get($user, "profile.district_code", null),
                'district_name'    => object_get($user, "profile.district.full_name", null),
                'ward_code'        => object_get($user, "profile.ward_code", null),
                'ward_name'        => object_get($user, "profile.ward.full_name", null),
                'group_id'         => $user->group_id,
                'parent_id'        => $user->parent_id ?? null,
                'parent_name'      => $user->parentLeader->name ?? null,
                'parent_code'      => $user->parentLeader->code ?? null,
                'group_name'       => object_get($user, 'group.name'),
                'group_code'       => object_get($user, 'group.code'),
                'distributor_id'   => object_get($user, 'distributor.id', null),
                'distributor_name' => object_get($user, 'distributor.name', null),
                'distributor_code' => object_get($user, 'distributor.code', null),
                'is_active'        => $user->is_active,
                'created_at'       => date('d-m-Y', strtotime($user->created_at)),
                'updated_at'       => date('d-m-Y', strtotime($user->updated_at)),
                'is_logged'        => $user->is_logged,
                'total_price'      => number_format(round($total)) . 'đ',
                'total_crm'        => $totalSellerOrder ?? 0,
                'total_seller_crm' => $totalSellerOrderAll ?? 0,
                'total_monitor'    => $totalSellerOrderMonitor ?? null
            ];
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}

<?php

/**
 * Date: 3/20/2019
 * Time: 2:41 PM
 */

namespace App\V1\Transformers\User;

use App\Card;
use App\Order;
use App\Image;
use App\MembershipRank;
use App\Supports\Message;
use App\TM;
use App\Supports\TM_Error;
use App\User;
use App\UserSession;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;

class UserCustomerProfileTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        $birthday    = !empty($user->profile->birthday) ? date("d-m-Y", strtotime($user->profile->birthday)) : null;
        $iconDefault = MembershipRank::model()->where('code', 'NEW')->first();
        $images      = [];
        if (!empty($user->profile->personal_image_ids)) {
            $images = Image::model()->select(DB::raw("concat('" . (url() . "/v0/images/") . "', url) as url"))
                ->whereIn('id', explode(",", $user->profile->personal_image_ids))
                ->get()->pluck('url')->toArray();
        }
        $total_sales = $user->orders()->where(['status' => ORDER_STATUS_COMPLETED])->sum('total_price');
        $total_order = $user->orders()->where([
            'status'     => ORDER_STATUS_COMPLETED,
            'order_type' => ORDER_TYPE_AGENCY,
        ])->whereBetween('updated_at', [date("Y-m-1", time()), date("Y-m-d", time())])->sum('total_price');
        $saving      = Order::model()->where([
            'customer_id' => object_get($user, "id", null),
            'status'      => 'COMPLETED'
        ])->get();
        $discount    = 0;
        foreach ($saving as $save) {
            $discount += $save['saving'];
        }
        $rebate  = $user->getRebate($user, $total_order) ?? 0;
        $point   = $user->getCustomerPoint();
        $ranking = $user->getRankByTotalSale($user);
        //        $ranking     = $user->getRank($point);
        //        $nextRanking = $user->getNextRank($point);
        $nextRanking = $user->getNextRankByTotalSale($ranking);
        try {
            $profile      = [
                'id'                      => object_get($user, "id", null),
                'code'                    => object_get($user, "code", null),
                'email'                   => object_get($user, "profile.email", null),
                'phone'                   => object_get($user, "profile.phone", null),
                'first_name'              => object_get($user, "profile.first_name", null),
                'last_name'               => object_get($user, "profile.last_name", null),
                'short_name'              => object_get($user, "profile.short_name", null),
                'full_name'               => object_get($user, "profile.full_name", null),
                'address'                 => object_get($user, "profile.address", null),
                'gg_id'                   => object_get($user, "gg_id", null),
                'fb_id'                   => object_get($user, "fb_id", null),
                'zl_id'                   => object_get($user, "zl_id", null),
                'avatar'                  => object_get($user, "avatar", null),
                'avatar_url'              => object_get($user, "avatar_url", null),
                'group_id'                => object_get($user, "group_id", null),
                'group_code'              => object_get($user, "group_code", null),
                'group_name'              => object_get($user, "group_name", null),
                'temp_address'            => object_get($user, "profile.temp_address", null),
                'registed_address'        => object_get($user, "profile.registed_address", null),
                'marital_status'          => object_get($user, "profile.marital_status", null),
                'work_experience'         => object_get($user, "profile.work_experience", null),
                'city_code'               => object_get($user, 'profile.city_code', null),
                'city_name'               => object_get($user, "profile.city.name", null),
                'district_code'           => object_get($user, "profile.district_code", null),
                'district_name'           => object_get($user, "profile.district.name", null),
                'ward_code'               => object_get($user, "profile.ward_code", null),
                'ward_name'               => object_get($user, "profile.ward.name", null),
                'birthday'                => $birthday,
                'gender'                  => Message::get("profiles.gender." . Arr::get($user, 'profile.gender', "O")),
                'id_number'               => object_get($user, "profile.id_number", null),
                'id_number_at'            => object_get($user, "profile.id_number_at", null),
                'id_number_place'         => object_get($user, "profile.id_number_place", null),
                'id_images'               => !empty($id_images = object_get($user, "profile.id_images", null)) ? explode(',', $id_images) : [],
                'transaction_total'       => object_get($user, "profile.transaction_total", null),
                'transaction_cancel'      => object_get($user, "profile.transaction_cancel", null),
                'point'                   => (int)$point,
                //'commission_rate'          => $user->getCustomerCommissionThisMonth($user),
                //'commission_referral_rate' => $user->getCustomerCommissionReferralThisMonth($user),
                'rank_name'               => $ranking['name'] ?? null,
                'rank_icon'               => $ranking['icon'] ?? null,
                'rank_id'                 => $ranking['id'] ?? null,
                'rank_code'               => $ranking['code'] ?? 'NEW',
                'money_total'             => object_get($user, "profile.money_total", null),
                'lat'                     => object_get($user, "profile.lat", null),
                'long'                    => object_get($user, "profile.long", null),
                'latlong'                 => object_get($user, "profile.lat") . ',' . object_get($user, "profile.long"),
                'ready_work'              => object_get($user, "profile.ready_work"),
                'personal_verified'       => object_get($user, "profile.personal_verified"),
                'personal_images'         => $images,
                'introduce_from'          => object_get($user, "profile.introduce_from"),
                'customer_introduce'      => object_get($user, "profile.customer_introduce"),
                'occupation'              => object_get($user, "profile.occupation"),
                'education'               => object_get($user, "profile.education"),
                'home_phone'              => object_get($user, "profile.home_phone"),
                'total_order_price'       => $total_order,
                'total_sales'             => $total_sales,
                'total_discount'          => $discount,
                'total_discount_formated' => number_format($discount) . ' đ',
                'rebate'                  => $rebate,
                'is_active'               => object_get($user, "profile.is_active", null),
            ];
            $permissions  = TM::getCurrentPermission();
            $avatar_url   = object_get($user, "profile.avatar_url");
            $avatar       = !empty($avatar_url) ? $avatar_url : (!empty($user->profile->avatar) ? $user->profile->avatar : null);
            $user_session = UserSession::model()->where('user_id', $user->id)->where('deleted', 0)->first();
            $totalMembers = 0;
            $level3       = $user->user_level3_ids ? explode(",", $user->user_level3_ids) : [];
            if ($user->level_number == 1) {
                $level2       = $user->user_level2_ids ? explode(",", $user->user_level2_ids) : [];
                $totalMembers = count($level2) + count($level3);
            } elseif ($user->level_number == 2) {
                $totalMembers = count($level3);
            }
            return [
                'id'                      => $user->id,
                'code'                    => $user->code,
                'phone'                   => $user->phone,
                'email'                   => $user->email,
                'type'                    => $user->type,
                'note'                    => $user->note,
                'fb_id'                   => $user->fb_id,
                'gg_id'                   => $user->gg_id,
                'zl_id'                   => $user->zl_id,
                'group_id'                => $user->group_id,
                'group_code'              => Arr::get($user->group, 'code'),
                'group_name'              => Arr::get($user->group, 'name'),
                'register_at'             => !empty($user->register_at) ? date('d-m-Y', strtotime($user->register_at)) : null,
                'card_ids'                => $user->card_ids,
                'card_name'               => $this->stringToCard($user->card_ids),
                'referral_code'           => $user->referral_code,
                'start_work_at'           => !empty($user->start_work_at) ? date('d-m-Y', strtotime($user->start_work_at)) : null,
                'work_status'             => $user->work_status,
                'role_code'               => object_get($user, "role.code", null),
                'role_name'               => object_get($user, "role.name", null),
                'first_name'              => object_get($user, "profile.first_name", null),
                'last_name'               => object_get($user, "profile.last_name", null),
                'short_name'              => object_get($user, "profile.short_name", null),
                'full_name'               => object_get($user, "profile.full_name", null),
                'address'                 => object_get($user, "profile.address", null),
                'branch_name'             => object_get($user, "profile.branch_name", null),
                'birthday'                => $birthday,
                'gender'                  => Arr::get($user, 'profile.gender', "O"),
                'gender_name'             => Message::get("profiles.gender." . Arr::get($user, 'profile.gender', "O")),
                'avatar'                  => $avatar ?? null,
                'id_number'               => object_get($user, "profile.id_number", null),
                'register_city'           => $user->register_city,
                //'register_city_name' => object_get($user, "master_data.name"),
                'register_district'       => $user->register_district,
                //'register_district_name' => object_get($user, "master_data.name"),
                'partner_type'            => $user->partner_type,
                'is_active'               => $user->is_active,
                'device_token'            => $user_session->device_token ?? null,
                'socket_id'               => $user_session->socket_id ?? null,
                'profile'                 => $profile,
                'point'                   => $user->point,
                'ranking_id'              => $user->ranking_id,
                'ranking_code'            => $user->ranking_code,
                'ranking_name'            => object_get($user, 'membership.name'),
                'parent_id'               => object_get($user, 'parentLeader.id', null),
                'parent_name'               => object_get($user, 'parentLeader.name', null),
                'ranking_icon'            => object_get($user, 'membership.icon'),
                'ranking_point'           => object_get($user, 'membership.point'),
                'next_ranking_code'       => $nextRanking['code'] ?? null,
                'next_ranking_name'       => $nextRanking['name'] ?? null,
                'next_ranking_icon'       => $nextRanking['icon'] ?? null,
                'next_ranking_point'      => $nextRanking['point'] ?? null,
                'next_ranking_back'       => $next_point = $nextRanking['next_point'] ?? null,
                'next_ranking_text'       => $next_point ? Message::get('V063', $next_point, $nextRanking['name']) : null,
                'permissions'             => $permissions,
                'star'                    => $user->star,
                'introduce_from'          => object_get($user, "profile.introduce_from"),
                'customer_introduce'      => object_get($user, "profile.customer_introduce"),
                'est_revenues'            => object_get($user, 'est_revenues', null),
                'account_status'          => object_get($user, 'account_status', null),
                'customer_type'           => object_get($user, 'customer_type', null),
                'reference_phone'         => object_get($user, 'reference_phone', null),
                'agent_register'          => object_get($user, 'agent_register', null),
                'area_details'            => $user->userArea->map(function ($item) {
                    return $item->only([
                        'id',
                        'code',
                        'name',
                    ]);
                }),
                'user_level'              => $user->level_number,
                'total_sales'             => $total_sales ?? null,
                'total_sales_formatted'   => !empty($total_sales) ? number_format($total_sales) . " đ" : null,
                'total_discount'          => $discount,
                'total_discount_formated' => number_format($discount) . ' đ',
                'total_members'           => $totalMembers,
                'companies'               => $user->userCompanies,
                'total_order_price_month' => $total_order,
                //                'total_order_price_month_formated' => !empty($total_order) ? number_format($total_order) . " đ" : null,
                'rebate'                  => $rebate,
                'created_at'              => date('d-m-Y', strtotime($user->created_at)),
                'updated_at'              => !empty($user->updated_at) ? date(
                    'd-m-Y',
                    strtotime($user->updated_at)
                ) : null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }

    private function stringToCard($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $card = Card::model()->select(['name'])->whereIn('id', explode(",", $ids))->get();
        $card = array_pluck($card, 'name');
        $card = implode(', ', $card);
        return $card;
    }
}

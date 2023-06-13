<?php

/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App\V1\Transformers\User;

use App\Card;
use App\Image;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\User;
use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;
use tests\Mockery\Adapter\Phpunit\EmptyTestCase;

/**
 * Class UserTransformer
 *
 * @package App\V1\Transformers
 */
class UserTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        try {
            $avatar = object_get($user, 'profile.avatar', null);
            $avatar = $avatar ? url('/v0') . "/img/" . "uploads," . $avatar : null;

            $gender = object_get($user, 'profile.gender', 'O');

            $images = [];
            if (!empty($user->profile->personal_image_ids)) {
                $images = Image::model()->select(DB::raw("concat('" . (url() . "/v0/images/") . "', url) as url"))
                    ->whereIn('id', explode(",", $user->profile->personal_image_ids))
                    ->get()->pluck('url')->toArray();
            }

            $operationField = object_get($user, 'profile.operation_field', null);
            $operationObj   = $user->master_data($operationField);

            // Companies
            $userCompanies = $user->userCompanies;
            $total_order   = $user->orders()->where(['status' => ORDER_STATUS_COMPLETED])->sum('total_price');
            $rebate        = $user->getRebate($user, $total_order) ?? 0;
            $shippers      = $user->distriHasShipper;
            return [
                'id'                     => $user->id,
                'code'                   => $user->code,
                'phone'                  => $user->phone,
                'email'                  => $user->email,
                'type'                   => $user->type,
                'first_name'             => object_get($user, "profile.first_name", null),
                'last_name'              => object_get($user, "profile.last_name", null),
                'short_name'             => object_get($user, "profile.short_name", null),
                'full_name'              => object_get($user, "profile.full_name", null),
                'temp_address'           => object_get($user, "profile.temp_address", null),
                'address'                => object_get($user, "profile.address", null),
                'birthday'               => object_get($user, 'profile.birthday', null),
                'distributor_id'         => object_get($user, 'distributor_id', null),
                'distributor_code'       => object_get($user, 'distributor.code', null),
                'distributor_name'       => object_get($user, 'distributor.profile.full_name', null),
                'distributor_group_id'   => object_get($user, 'distributor.group.id', null),
                'distributor_group_code' => object_get($user, 'distributor.group.code', null),
                'distributor_group_name' => object_get($user, 'distributor.group.name', null),
                'gender'                 => $gender,
                'gender_name'            => Message::get("profiles.gender." . $gender),
                'lat'                    => object_get($user, 'profile.lat', null),
                'long'                   => object_get($user, 'profile.long', null),
                'latlong'                => object_get($user, "profile.lat") . ',' . object_get($user, "profile.long"),
                'ready_work'             => object_get($user, "profile.ready_work"),
                'avatar'                 => $avatar ?? null,
                'id_number'              => object_get($user, "profile.id_number", null),
                'point'                  => $user->point,
                'qty_max_day'            => $user->qty_max_day,
                'min_amt'                => $user->min_amt,
                'max_amt'                => $user->max_amt,
                'type_delivery_hub'      =>$user->type_delivery_hub,
                'maximum_volume'        =>$user->maximum_volume,
                'is_transport'          => $user->is_transport,
                'is_vtp'                => $user->is_vtp,
                'is_vnp'                => $user->is_vnp,
                'is_grab'               => $user->is_grab,
                'is_self_delivery'                => $user->is_self_delivery,
                'caller_start_time'        =>$user->caller_start_time,
                'caller_end_time'        =>$user->caller_end_time,
                //'point_type'             => object_get($user, "profile.point_type", null),
                'membership_rank'        => $user->ranking_id,
                'membership_rank_code'   => $user->ranking_code,
                'membership_rank_name'   => object_get($user, "membership.name", null),
                'membership_rank_icon'   => object_get($user, "membership.icon", null),
                'role_id'                => $user->role_id,
                'parent_leader_id'              => !empty($user->parentLeader->role->code) && $user->parentLeader->role->code == USER_ROLE_LEADER ? $user->parent_id : null,
                'parent_leader_name'            => !empty($user->parentLeader->role->code) && $user->parentLeader->role->code == USER_ROLE_LEADER ?$user->parentLeader->name : null,
                'parent_leader_code'            => !empty($user->parentLeader->role->code) && $user->parentLeader->role->code == USER_ROLE_LEADER ?$user->parentLeader->code : null,
                'parent_id'              => !empty($user->parentLeader->role->code) && $user->parentLeader->role->code != USER_ROLE_LEADER ? $user->parent_id : null,
                'parent_name'            => !empty($user->parentLeader->role->code) && $user->parentLeader->role->code != USER_ROLE_LEADER ?$user->parentLeader->name : null,
                'parent_code'            => !empty($user->parentLeader->role->code) && $user->parentLeader->role->code != USER_ROLE_LEADER ? $user->parentLeader->code : null,
                'register_at'            => !empty($user->register_at) ? date(
                    'Y-m-d',
                    strtotime($user->register_at)
                ) : date('d-m-Y', strtotime($user->created_at)),
                'card_ids'               => $user->card_ids,
                'card_name'              => $this->stringToCard($user->card_ids),
                'referral_code'          => $user->referral_code,
                'start_work_at'          => !empty($user->start_work_at) ? date(
                    'd-m-Y',
                    strtotime($user->start_work_at)
                ) : null,
                'work_status'            => $user->work_status,
                'personal_verified'      => object_get($user, "profile.personal_verified"),
                'personal_images'        => $images,

                'register_city'          => object_get($user, 'register_city', null),
                'register_city_name'     => object_get($user, "registerCity.type", null) . object_get(
                    $user,
                    "registerCity.name",
                    null
                ),
                'register_district'      => !empty($user->register_district) ? explode(
                    ",",
                    $user->register_district
                ) : null,
                'register_district_name' => !empty($user->register_district) ? $user->getDistrictNames($user->register_district) : null,
                'ref_code'               => $user->ref_code,
                'introduce_from'         => object_get($user, "profile.introduce_from"),
                'customer_introduce'     => object_get($user, "profile.customer_introduce"),
                'work_experience'        => object_get($user, "profile.work_experience", null),
                'partner_type'           => $user->partner_type,
                'representative'         => object_get($user, 'profile.representative'),
                'operation_field'        => $operationField,
                'operation_field_id'     => $operationObj->id ?? null,
                'operation_field_name'   => $operationObj->name ?? null,
                'companies'              => $userCompanies,
                'stores'                 => $user->userStores,

                'company_id'          => $user->company_id,
                'company_code'        => object_get($user, 'company.code'),
                'company_name'        => object_get($user, 'company.name'),
                'area_id'             => !empty($user->area_id) ? explode(",", $user->area_id) : null,
                'area_details'        => $user->userArea->map(function ($item) {
                    return $item->only([
                        'id',
                        'code',
                        'name',
                    ]);
                }),
                'register_areas'        => $user->registerArea->map(function ($item) {
                    return $item->only([
                        'id',
                        'user_id',
                        'user_code',
                        'user_name',
                        'city_code',
                        'city_name',
                        'district_code',
                        'district_name',
                        'ward_code',
                        'ward_name',
                        'store_id',
                        'company_id',
                    ]);
                }),
                'zone_hub_ids'        => !empty($user->zone_hub_ids) ? explode(",", $user->zone_hub_ids) : [],
                'zone_hub'            => $user->userZoneHub->map(function ($item) {
                    return $item->only([
                        'id',
                        'name',
                        'latlong',
                        'description'
                    ]);
                }),
                'group_id'            => $user->group_id,
                'group_code'          => object_get($user, 'group.code'),
                'group_name'          => object_get($user, 'group.name'),
                'distributor_center_id'     => $user->distributor_center_id,
                'distributor_center_code'   => $user->distributor_center_code,
                'distributor_center_name'   => $user->distributor_center_name,
                'user_level'          => $user->level_number,
                'indentity_card'      => object_get($user, 'profile.indentity_card'),
                'landline_phone'      => object_get($user, 'profile.landline_phone'),
                'occupation'          => object_get($user, 'profile.occupation'),
                'education'           => object_get($user, 'profile.education'),
                'marital_status'      => object_get($user, 'profile.marital_status'),
                'city_code'           => object_get($user, 'profile.city_code'),
                'city_name'           => object_get($user, 'profile.city.type') . ' ' . object_get($user, 'profile.city.name'),
                'district_code'       => object_get($user, 'profile.district_code'),
                'district_name'       => object_get($user, 'profile.district.type') . " " . object_get(
                    $user,
                    'profile.district.name'
                ),
                'ward_code'           => object_get($user, 'profile.ward_code'),
                'ward_name'           => object_get($user, 'profile.ward.type') .' '. object_get($user, 'profile.ward.name'),
                'is_partner'          => $user->is_partner,
                'is_active'           => $user->is_active,
                'est_revenues'        => object_get($user, 'est_revenues', null),
                'tax'                 => object_get($user, 'tax', null),
                'account_status'      => object_get($user, 'account_status', null),
                'bank_account_name'   => object_get($user, 'bank_account_name', null),
                'bank_account_number' => object_get($user, 'bank_account_number', null),
                'bank_branch'         => object_get($user, 'bank_branch', null),
                'customer_type'       => object_get($user, 'customer_type', null),
                'reference_phone'     => object_get($user, 'reference_phone', null),
                'agent_register'      => object_get($user, 'agent_register', null),
                'total_order_price'   => $total_order,
                'rebate'              => $rebate,
                'shippers'            => $shippers->toArray(),
                'created_at'          => date('d-m-Y', strtotime($user->created_at)),
                'updated_at'          => date('d-m-Y', strtotime($user->updated_at)),
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

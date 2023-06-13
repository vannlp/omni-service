<?php

namespace App;

use App\Supports\DataUser;
use App\V1\Models\OrderModel;
use App\V1\Models\SettingModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;

    protected $table = 'users';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden
        = [
            'password',
        ];
    protected $fillable
        = [
            'user_customer_id',
            'phone',
            'code',
            'name',
            'password',
            'email',
            'gg_id',
            'fb_id',
            'zl_id',
            'verify_code',
            'expired_code',
            'role_id',
            'zone_hub_ids',
            'note',
            'type',
            'register_at',
            'card_ids',
            'referral_code',
            'start_work_at',
            'point',
            'qty_max_day',
            'min_amt',
            'max_amt',
            'point_used',
            'ranking_id',
            'ranking_code',
            'work_status',
            'personal_verified',
            'personal_image_ids',
            'is_super',
            'ref_code',
            'register_city',
            'bank_id',
            'bank_account_name',
            'bank_account_number',
            'register_district',
            'partner_type',
            'company_id',
            'store_id',
            'group_id',
            'group_code',
            'group_name',
            'area_id',
            'is_partner',
            'est_revenues',
            'customer_type',
            'account_status',
            'reference_id',
            'reference_code',
            'reference_name',
            'reference_phone',
            'tax',
            'bank_branch',
            'level_number',
            'user_level1_id',
            'user_level1_code',
            'user_level1_name',
            'user_level2_ids',
            'user_level2_codes',
            'user_level2_data',
            'user_level3_ids',
            'user_level3_codes',
            'user_level3_data',
            'distributor_center_id',
            'distributor_center_code',
            'distributor_center_name',
            'distributor_id',
            'distributor_code',
            'distributor_name',
            'agent_register',
            'data_sync',
            'channel_type',
            'is_active',
            'is_logged',
            'deleted',
            'created_at',
            'created_by',
            'updated_by',
            'updated_at',
            'deleted',
            'turn_rotation',
            'parent_id',
            'is_transport',
            'maximum_volume',
            'qty_remaining_single',
            'type_delivery_hub',
            'caller_start_time',
            'caller_end_time',
            'is_vtp',
            'is_vnp',
            'is_grab',
            'is_self_delivery'
        ];

    public static final function model()
    {
        $classStr = get_called_class();
        /** @var Model $class */
        $class = new $classStr();
        return $class::whereNull($class->getTable() . '.deleted_at');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function shipping_address()
    {
        return $this->hasOne(ShippingAddress::class, 'user_id', 'id')->where('is_default', 1);
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }

    public function customer()
    {
        return $this->hasOne(__NAMESPACE__ . '\Customer', 'code', 'code');
    }

    public function role()
    {
        return $this->hasOne(__NAMESPACE__ . '\Role', 'id', 'role_id');
    }

    public function parentLeader()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'parent_id');
    }

    public function customerGroups()
    {
        return $this->hasMany(__NAMESPACE__ . '\UserCustomerGroup', 'user_id', 'id');
    }

    public function membership()
    {
        return $this->hasOne(__NAMESPACE__ . '\MembershipRank', 'id', 'ranking_id')
            ->where('company_id', TM::getCurrentCompanyId());
    }

    public function ranking()
    {
        return $this->hasOne(__NAMESPACE__ . '\MembershipRank', 'id', 'ranking_id');
    }

    public function master_data($code)
    {
        return MasterData::model()->where('code', $code)->first();
    }

    public function registerCity()
    {
        return $this->hasOne(__NAMESPACE__ . '\City', 'code', 'register_city');
    }

    public function registerDistrict()
    {
        return $this->hasOne(__NAMESPACE__ . '\District', 'code', 'register_district');
    }

    public function getDistrictNames(string $ids)
    {
        $districts = District::model()->select(['id', DB::raw('concat(type, " ", name) as district')])
            ->whereIn('id', explode(",", $ids))
            ->get()->pluck('district')->toArray();

        return !empty($districts) ? implode(" - ", $districts) : null;
    }

    public function userCompanies()
    {
        return $this->belongsTo(UserCompany::class, 'id', 'user_id');
    }

    public function userStores()
    {
        return $this->hasMany(UserStore::class, 'user_id', 'id');
    }
    public function productHub()
    {
        return $this->hasMany(ProductHub::class, 'user_id', 'id');
    }
    public function group()
    {
        return $this->hasOne(UserGroup::class, 'id', 'group_id');
    }

    public function distributor()
    {
        return $this->hasOne(User::class, 'id', 'distributor_id');
    }

//    public function zoneHub()
//    {
//        return $this->hasOne(__NAMESPACE__ . '\ZoneHub', 'user_id', 'id');
//    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
    }

    public function userArea()
    {
        return $this->belongsToMany(Area::class, 'user_areas');
    }

    public function userZoneHub()
    {
        return $this->belongsToMany(ZoneHub::class, 'user_has_zone_hubs');
    }

    public function zoneHub()
    {
        return $this->belongsTo(ZoneHub::class, 'zone_hub_id', 'id');
    }

    public function userReference()
    {
        return $this->hasOne(UserReference::class, 'user_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }
    public function sellerOrder()
    {
        return $this->hasMany(Order::class, 'seller_id', 'id');
    }
    public function countOrder()
    {
        return $this->hasMany(Order::class, 'seller_id', 'id')
            ->whereBetween('created_at',[date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59')]);;
    }

    public function getRebate($user, $total_order)
    {
        $setting = Setting::model()->where([
            'code'       => 'CUSTOMER-COMMISSIONS',
            'company_id' => TM::getCurrentCompanyId(),
            'store_id'   => TM::getCurrentStoreId(),
        ])->first();
        if (!empty($setting)) {
            $data = Arr::get($setting, 'data', null);
            $data = json_decode($data);
            $data = array_pluck($data, 'key', 'value');
            ksort($data);
            $result = 0;
            foreach ($data as $key => $datum) {
                if ($total_order >= $datum) {
                    $result = $key;
                    break;
                }
            }
//            $result = $result ?? 0;
//            if ($result != 0) {
//                $rebate = $total_order * ($result / 100);
//            } else {
//                $rebate = 0;
//            }

            return $user->group_code == "HUB" ? $result : 0;
        }
    }

    public function getCustomerPoint()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id')
            ->where([
                'store_id' => TM::getCurrentStoreId(),
                'status'   => ORDER_STATUS_COMPLETED,
            ])->sum('customer_point');
    }

    public function getCustomerCommissionThisMonth(User $user)
    {
        $input['user_ids'] = explode(",",
            $user->id . "," . $user->user_level1_id . "," . $user->user_level2_ids . "," . $user->user_level3_ids);
        $input['user_ids'] = array_unique(array_filter($input['user_ids']));

        $input['from'] = date("Y-m-01");
        $input['to']   = date('Y-m-d');

        // Get Setting
        $settingModel      = new SettingModel();
        $settingCommission = $settingModel->getDataForKey('CUSTOMER-COMMISSIONS', 'key');

        // Get Order
        $orderModel = new OrderModel();
        $orders     = $orderModel->getTotalSalesForCustomers($input);
        if (!empty($orders)) {
            $orders = $orderModel->calculateCustomerCommission($orders, $settingCommission);
        }

        if (empty($orders)) {
            return null;
        }

        foreach ($orders as $order) {
            if ($order['customer_id'] == $user->id) {
                return [
                    'rate'             => $order['rate'] . "%",
                    'total_sale'       => $order['total_sales'],
                    'total_group_sale' => $order['total_group_sales'] - $order['total_sales'],
                    'commission'       => $order['commission'],
                ];
            }
        }

        return null;
    }

    public function getCustomerCommissionReferralThisMonth(User $user)
    {
        $input['user_ids'] = explode(",",
            $user->id . "," . $user->user_level1_id . "," . $user->user_level2_ids . "," . $user->user_level3_ids);
        $input['user_ids'] = array_unique(array_filter($input['user_ids']));

        $input['from'] = date("Y-m-01");
        $input['to']   = date('Y-m-d');

        // Get Setting
        $settingModel = new SettingModel();
        $setting      = $settingModel->getForKey('CUSTOMER-REFERRAL-COMMISSIONS', 'key');

        // Get Order
        $orderModel = new OrderModel();
        $orders     = $orderModel->getTotalSalesForCustomers($input);
        if (!empty($orders)) {
            $orders = $orderModel->calculateCustomerReferralCommission($orders, $setting);
        }

        if (empty($orders)) {
            return null;
        }

        foreach ($orders as $order) {
            if ($order['customer_id'] == $user->id) {
                return [
                    'rate'             => !empty($order['rate']) ? $order['rate'] . "%" : "0%",
                    'total_sale'       => $order['total_sales'],
                    'total_group_sale' => $order['total_group_sales'],
                    'commission'       => $order['commission'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * @param $point
     * @return array
     */
    public function getRank($point)
    {
//        if (empty($point) || $point <= 0) {
//            return [];
//        }

        $ranks  = MembershipRank::model()->where('company_id', TM::getCurrentCompanyId())
            ->orderBy('point')->get()->toArray();
        $output = [];
        foreach ($ranks as $rank) {
            if ($rank['point'] > $point) {
                break;
            }

            $output = $rank;
        }

        return $output;
    }

    public function getRankByTotalSale($user)
    {
        $now         = date("Y-m-d", time());
        $user        = User::find($user->id);
        $currentRank = $user->ranking ? $user->ranking->toArray() : [];
        $ranks       = MembershipRank::model()
            ->where('company_id', TM::getCurrentCompanyId())
            ->whereRaw("'{$now}' BETWEEN date_start AND date_end")
            ->orderBy('total_sale')
            ->get()
            ->toArray();

        if ($ranks) {
            $rankDateStart = $ranks[0]['date_start'] ?? null;
            $rankDateEnd   = $ranks[0]['date_end'] ?? null;
            if ($currentRank) {


                $currentRankDateStart = date("Y-m-d", strtotime($currentRank['date_start']));
                $currentRankDateEnd   = date("Y-m-d", strtotime($currentRank['date_end']));
                //Update Rank Before New Update
                if ($currentRankDateEnd < $rankDateStart) {
                    $totalSales = $user->orders()
                        ->where('status', ORDER_STATUS_COMPLETED)
                        ->whereBetween('updated_at', [$currentRankDateStart, $currentRankDateEnd])
                        ->sum('total_price');

                    $rankCurrents  = MembershipRank::model()
                        ->where('company_id', TM::getCurrentCompanyId())
                        ->where('date_start', '>=', $currentRankDateStart)
                        ->where('date_start', '<=', $currentRankDateEnd)
                        ->orderBy('total_sale')
                        ->get()
                        ->toArray();
                    $outputCurrent = [];
                    foreach ($rankCurrents as $rank) {
                        if ($rank['total_sale'] > $totalSales) {
                            break;
                        }
                        $outputCurrent = $rank;
                    }

                    if ($outputCurrent) {
                        $user->ranking_id   = $outputCurrent['id'];
                        $user->ranking_code = $outputCurrent['code'];
                    } else {
                        $user->ranking_id   = null;
                        $user->ranking_code = null;
                    }
                }
            }
            $total_sales = $user->orders()
                ->where('status', ORDER_STATUS_COMPLETED)
                ->whereBetween('updated_at', [$rankDateStart, $rankDateEnd])
                ->sum('total_price');

            $output = [];

            foreach ($ranks as $rank) {
                if ($rank['total_sale'] > $total_sales) {
                    break;
                }
                $output = $rank;
            }

            if ($output && $output['total_sale'] ?? 0 > $currentRank['total_sale'] ?? 0) {
                $user->ranking_id   = $output['id'];
                $user->ranking_code = $output['code'];
            }
        }

        $user->save();

        return $output ?? [];
    }

    public function getNextRankByTotalSale($ranking)
    {
        $totalSale = 0;
        if ($ranking) {
            $totalSale = $ranking['total_sale'];
            $ranks     = MembershipRank::model()
                ->where('company_id', TM::getCurrentCompanyId())
                ->orderBy('total_sale')
                ->where('total_sale', '>', $totalSale)->first();
        }

        $output = [
            'code'       => $ranks->code ?? null,
            'name'       => $ranks->name ?? null,
            'icon'       => $ranks->icon ?? null,
            'point'      => $ranks->point ?? null,
            'total_sale' => $ranks->total_sale ?? null,
            'next_point' => !empty($ranks->total_sale) && $ranks->total_sale - $totalSale > 0 ? $ranks->total_sale - $totalSale : 0,
        ];

        return $output;
    }

    public function getNextRank($point)
    {
        $ranks  = MembershipRank::model()->where('company_id', TM::getCurrentCompanyId())
            ->orderBy('point')->where('point', '>', $point)->first();
        $output = [
            'code'       => $ranks->code ?? null,
            'name'       => $ranks->name ?? null,
            'icon'       => $ranks->icon ?? null,
            'point'      => $ranks->point ?? null,
            'next_point' => !empty($ranks->point) && $ranks->point - $point > 0 ? $ranks->point - $point : 0,
        ];

        return $output;
    }

    public function distriHasShipper()
    {
        return $this->hasMany(DistributorHasShipper::class, 'distributor_id', 'id');
    }

    public function registerArea()
    {
        return $this->hasMany(RegisterArea::class, 'user_id', 'id');
    }
    public function scopeSearchHub($query, $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();

        if ($name = $request->get('name')) {
            $query->whereRaw("name LIKE '%{$name}%'");
        }
        $query->where('group_code',USER_GROUP_DISTRIBUTOR);
        $query->orWhere('group_code',USER_GROUP_HUB);
        $query->orWhere('group_code',USER_GROUP_DISTRIBUTOR_CENTER);
        return $query;
    }
}

<?php

/**
 * User: Administrator
 * Date: 28/09/2018
 * Time: 09:32 PM
 */

namespace App\V1\Models;

use App\Category;
use App\DistributorCenter;
use App\City;
use App\Company;
use App\CustomerInformation;
use App\Distributor;
use App\DistributorHasShipper;
use App\District;
use App\Image;
use App\Profile;
use App\RegisterArea;
use App\Role;
use App\Store;
use App\TM;
use App\Supports\Message;
use App\User;
use App\UserCompany;
use App\UserGroup;
use App\UserStore;
use App\ZoneHub;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use App\Ward;

class UserModel extends AbstractModel
{
    public function __construct(User $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id      = !empty($input['id']) ? $input['id'] : 0;
        $allRole = Role::model()->pluck('id', 'code')->toArray();
        $phone   = "";
        $parentId = !empty($input['parent_leader_id']) ? $input['parent_leader_id'] : (!empty($input['parent_id']) ? $input['parent_id']  : null);
        if (!empty($input['phone'])) {
            $phone = str_replace(" ", "", $input['phone']);
            $phone = preg_replace('/\D/', '', $phone);
        }

        // Phone unique
        if (!empty($input['phone'])) {
            $checkPhone = $this->model->where('phone', $input['phone'])
                ->where('store_id', !empty($input['store_id']) ? $input['store_id'] : TM::getCurrentStoreId())
                ->where('company_id', !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId())
                ->where(function ($query) use ($id, $input) {
                    if (!empty($id)) {
                        $query->where('id', '!=', $id);
                    }
                    if(!empty($input['type'])){
                        $query->where('type',$input['type']);
                    }
                    if (!empty($input['group_code']) && in_array($input['group_code'],[USER_GROUP_DISTRIBUTOR,USER_GROUP_HUB,USER_GROUP_DISTRIBUTOR_CENTER])) {
                        $query->whereIn('users.group_code',[USER_GROUP_DISTRIBUTOR,USER_GROUP_HUB,USER_GROUP_DISTRIBUTOR_CENTER]);
                    }
                })->exists();

            if ($checkPhone) {
                throw new \Exception(Message::get("V007", Message::get('phone')));
            }
        }
        if (!empty($input['name']) && strlen($input['name']) < 2) {
            throw new \Exception(Message::get("error_name", 2));
        }

        if (!empty($input['zone_hub_ids'])) {
            $userZoneHubs = $input['zone_hub_ids'] ?? [];
            $zone_hub_ids = implode(",", $userZoneHubs);
            foreach ($userZoneHubs as $userZoneHub) {
                $zoneHub = ZoneHub::where([
                    'company_id' => !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId(),
                    'id'         => $userZoneHub
                ])->first();
                if (empty($zoneHub)) {
                    throw new \Exception(Message::get("V003", "ID Zone Hub: #$userZoneHub"));
                }
            }
        }

        if (!empty($input['reference_phone'])) {
            $checkPhone = User::model()->where('phone', $input['reference_phone'])
                ->whereHas('userStores', function ($q) {
                    $q->where('store_id', !empty($input['store_id']) ? $input['store_id'] : TM::getCurrentStoreId());
                })->first();
            if (empty($checkPhone)) {
                throw new \Exception(Message::get("V003", Message::get("reference_phone")));
            }
        }

        if (Arr::get($input, 'type') == USER_TYPE_CUSTOMER) {
            $userGroup = UserGroup::where('company_id', !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId())->where('is_default', 1)->first();
        }
        if (!empty($input['group_code'])) {
            $userGroup = UserGroup::model()->where([
                'code'       => $input['group_code'],
                'company_id' => !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId()
            ])->first();
        }
        $distributor = null;
        if (!empty($input['distributor_id'])) {
            $distributor = User::model()->where([
                'id'         => $input['distributor_id'],
                'company_id' => !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId(),
                'group_code' => USER_GROUP_DISTRIBUTOR
            ])->first();
        }
        if(!empty($input['parent_leader_id'])){
            $leader = User::model()->where([
                'id' =>$input['parent_leader_id'],
                'company_id' => !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId()
            ])->whereHas('role',function ($q){
                $q->where('code',USER_ROLE_LEADER);
            })->first();
            if(empty($leader)){
                throw new \Exception(Message::get("V003", Message::get("leader",$input['parent_leader_id'])));
            }
        }
        if ($id) {
            $user = User::find($id);
            if (empty($user)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            if(!empty($input['code']) && $input['code'] != $user->code) {
                Distributor::model()->where('code', $user->code)->update([
                    'code' => $input['code']
                ]);
            }

            // Email unique
            if (!empty($input['email'])) {
                $checkEmail = $this->model->where('email', $input['email'])
                    ->where('store_id', !empty($input['store_id']) ? $input['store_id'] : TM::getCurrentStoreId())
                    ->where('company_id', !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId())
                    ->where(function ($query) use ($id, $input) {
                        if (!empty($id)) {
                            $query->where('id', '!=', $id);
                        }
                    })->exists();

                if ($checkEmail && $user->email != $input['email']) {
                    throw new \Exception(Message::get("V007", Message::get('email')));
                }
            }
            if(!empty($input['code'])){
                $input['code'] = Str::lower($input['code']);
            }
            $user->phone   = !empty($phone) ? $phone : $user->phone;
            $user->code    = !empty($input['role_id']) && $input['role_id'] == 18 ? $phone : $input['code'];
            $user->name    = !empty($input['name']) ? $input['name'] : null;
            $user->email   = array_get($input, 'email', null);
            $user->role_id = array_get($input, 'role_id', $user->role_id);
            $user->parent_id = $parentId ?? $user->parent_id;
            $user->note    = array_get($input, 'note');

            $user->distributor_id   = $distributor->id ?? null;
            $user->distributor_code = $distributor->code ?? null;
            $user->distributor_name = $distributor->name ?? null;

            $user->type          = array_get($input, 'type', $user->type);
            $user->zone_hub_ids  = $zone_hub_ids ?? null;
            $user->verify_code   = mt_rand(100000, 999999);
            $user->expired_code  = date('Y-m-d H:i:s', strtotime("+5 minutes"));
            $user->register_at   = !empty($input['register_at']) ? date(
                'Y-m-d H:i:s',
                strtotime($input['register_at'])
            ) : $user->register_at;
            $user->card_ids      = !empty($input['card_ids']) ? implode(',', $input['card_ids']) : $user->card_ids;
            $user->referral_code = array_get($input, 'referral_code', $user->referral_code);
            $user->start_work_at = !empty($input['start_work_at']) ? date(
                'Y-m-d H:i:s',
                strtotime($input['start_work_at'])
            ) : $user->start_work_at;
            $user->start_work_at = array_get($input, 'work_status', $user->work_status);
            $user->is_active     = array_get($input, 'is_active', 1);
            if (!empty($input['password'])) {
                $user->password = password_hash($input['password'], PASSWORD_BCRYPT);
            }
            $user->partner_type = !empty($input['partner_type']) ? $input['partner_type'] : null;

            $user->group_id        = $userGroup->id ?? null;
            $user->group_code      = $userGroup->code ?? null;
            $user->group_name      = $userGroup->name ?? null;
            $user->reference_phone = !empty($input['reference_phone']) ? $input['reference_phone'] : $user->reference_phone;
            if(!empty($input['qty_max_day'])){
                if(!empty($user->qty_remaining_single) && !empty($user->qty_max_day)){
                    if((int)$input['qty_max_day'] > ((int)$user->qty_max_day - (int)$user->qty_remaining_single)){
                        $user->qty_remaining_single = (int)$input['qty_max_day'] -  ((int)$user->qty_max_day - (int)$user->qty_remaining_single);
                    }
                    else{
                        $user->qty_remaining_single = 0;
                    }
                }
            }
            $user->qty_max_day   = array_get($input, 'qty_max_day', null);
            $user->min_amt   = array_get($input, 'min_amt', null);
            $user->max_amt   = array_get($input, 'max_amt', null);
            $user->maximum_volume    = array_get($input, 'maximum_volume',null );
            $user->is_transport   = array_get($input, 'is_transport', 0 );
            $user->is_vtp   = array_get($input, 'is_vtp', $user->is_vtp );
            $user->is_vnp   = array_get($input, 'is_vnp', $user->is_vnp );
            $user->is_grab   = array_get($input, 'is_grab', $user->is_grab );
            $user->is_self_delivery   = array_get($input, 'is_self_delivery', $user->is_self_delivery );
            $user->type_delivery_hub   = array_get($input, 'type_delivery_hub');
            $user->caller_start_time = array_get($input, 'caller_start_time', $user->caller_start_time);
            $user->caller_end_time = array_get($input, 'caller_end_time', $user->caller_end_time);
            $user->area_id    = !empty($input['area_id']) ? implode(",", $input['area_id']) : null;
            $user->is_partner = !empty($input['is_partner']) ? 1 : 0;
            $user->company_id = !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId();
            $user->updated_by = TM::getCurrentUserId();
            if (!empty($input['area_id'])) {
                $user->area_id = implode(",", $input['area_id']);
                $user->userArea()->sync($input['area_id']);
            }
            if($user->group_code == USER_GROUP_DISTRIBUTOR){
                $user->distributor_center_id   = array_get($input, 'distributor_center_id', null);
                $user->distributor_center_code = array_get($input, 'distributor_center_code', null);
                $user->distributor_center_name = array_get($input, 'distributor_center_name', null);
            }
            $user->save();
            if (!empty($input['register_areas'])) {
                RegisterArea::where('user_id', $user->id)->delete();
                foreach ($input['register_areas'] as $item) {
                    unset($item['id']);
                    $item['user_id']    = $user->id;
                    $item['user_code']  = $user->code;
                    $item['user_phone'] = $user->phone;
                    $item['user_name']  = $user->name;
                    $item['store_id']   = $user->store_id;
                    $item['company_id'] = $user->company_id;
                    RegisterArea::insert($item);
                }
            }
        } else {
            // Email unique
            if (!empty($input['email'])) {
                $checkEmail = $this->model->where('email', $input['email'])
                    ->where('store_id', !empty($input['store_id']) ? $input['store_id'] : TM::getCurrentStoreId())
                    ->where('company_id', !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId())
                    ->where(function ($query) use ($id, $input) {
                        if (!empty($id)) {
                            $query->where('id', '!=', $id);
                        }
                    })->exists();

                if ($checkEmail) {
                    throw new \Exception(Message::get("V007", Message::get('email')));
                }
                //            $this->checkUnique(['email' => $input['email']], $id);
            }

            // Code unique
            $input['code'] = Str::lower($input['code']);

            if (!empty($input['code'])) {
                $checkCode = $this->model->where('code', $input['code'])
                    ->where('store_id', !empty($input['store_id']) ? $input['store_id'] : TM::getCurrentStoreId())
                    ->where('company_id', !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId())
                    ->where(function ($query) use ($id, $input) {
                        if (!empty($id)) {
                            $query->where('id', '!=', $id);
                        }
                    })->exists();

                if ($checkCode) {
                    throw new \Exception(Message::get("V007", Message::get('phone')));
                }
            }
            $param = [
                'phone'            => $phone,
//                'code'             => $input['code'],
                'code'             => !empty($input['role_id']) && $input['role_id'] == 18 ? $phone : $input['code'],
                'name'             => !empty($input['name']) ? $input['name'] : null,
                'email'            => array_get($input, 'email'),
                'role_id'          => array_get($input, 'role_id', $allRole[USER_ROLE_GUEST]),
                'parent_id'          => $parentId,
                'note'             => array_get($input, 'note'),
                'type'             => array_get($input, 'type'),
                'zone_hub_ids'     => $zone_hub_ids ?? null,
                'verify_code'      => mt_rand(100000, 999999),
                'expired_code'     => date('Y-m-d H:i:s', strtotime("+5 minutes")),
                'register_at'      => !empty($input['register_at']) ? date(
                    'Y-m-d H:i:s',
                    strtotime($input['register_at'])
                ) : null,
                'card_ids'         => !empty($input['card_ids']) ? implode(',', $input['card_ids']) : null,
                'referral_code'    => array_get($input, 'referral_code'),
                'start_work_at'    => !empty($input['start_work_at']) ? date(
                    'Y-m-d H:i:s',
                    strtotime($input['start_work_at'])
                ) : null,
                'work_status'      => array_get($input, 'work_status'),
                'partner_type'     => array_get($input, 'partner_type'),
                'group_id'         => $userGroup->id ?? null,
                'group_code'       => $userGroup->code ?? null,
                'group_name'       => $userGroup->name ?? null,
                'qty_max_day'         => array_get($input, 'qty_max_day', null),
                'min_amt'       => array_get($input, 'min_amt', null),
                'max_amt'       => array_get($input, 'max_amt', null),
                'maximum_volume'       => array_get($input, 'maximum_volume', null),
                'caller_start_time'       => array_get($input, 'caller_start_time', null),
                'caller_end_time'       => array_get($input, 'caller_end_time', null),
                'is_vtp'       => array_get($input, 'is_vtp', 0),
                'is_vnp'       => array_get($input, 'is_vnp', 0),
                'is_grab'       => array_get($input, 'is_grab', 0),
                'is_self_delivery'       => array_get($input, 'is_self_delivery', 0),
                'is_transport'       => array_get($input, 'is_transport', null),
                'type_delivery_hub'       => array_get($input, 'type_delivery_hub', null),
                'distributor_center_id'         => array_get($input, 'distributor_center_id', null),
                'distributor_center_code'       => array_get($input, 'distributor_center_code', null),
                'distributor_center_name'       => array_get($input, 'distributor_center_name', null),
                'area_id'          => !empty($input['area_id']) ? implode(",", $input['area_id']) : null,
                'is_partner'       => !empty($input['group_id']) ? 1 : 0,
                'distributor_id'   => $distributor->id ?? null,
                'distributor_code' => $distributor->code ?? null,
                'distributor_name' => $distributor->name ?? null,
                'reference_phone'  => $input['reference_phone'] ?? null,
                'is_active'        => array_get($input, 'is_active', 1),
                'store_id'         => !empty($input['store_id']) ? $input['store_id'] : TM::getCurrentStoreId(),
                'company_id'       => !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId(),
                'created_by'       => TM::getCurrentUserId(),
            ];
            if (!empty($input['password'])) {
                $param['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            }

            $user = $this->create($param);

            if (!empty($input['register_areas'])) {
                RegisterArea::where('user_id', $user->id)->delete();
                foreach ($input['register_areas'] as $item) {
                    unset($item['id']);
                    $item['user_id']    = $user->id;
                    $item['user_code']  = $user->code;
                    $item['user_phone'] = $user->phone;
                    $item['user_name']  = $user->name;
                    $item['store_id']   = $user->store_id;
                    $item['company_id'] = $user->company_id;
                    RegisterArea::insert($item);
                }
            }
        }

        //Save Info Customer
        if(empty($input['role_id']) || $input['role_id'] == 4 || $input['role_id'] == 18) {
            $cusInfo = CustomerInformation::where([
                'phone'    => "{$input['phone']}",
                'store_id' => TM::getCurrentStoreId()
            ])->first();
            if ($cusInfo) {
                $cusInfo->name           = array_get($input, 'name', $cusInfo->name);
                $cusInfo->phone          = array_get($input, 'phone', $cusInfo->phone);
                $cusInfo->email          = array_get($input, 'email', $cusInfo->email);
                $cusInfo->address        = array_get($input, 'address', $cusInfo->address);
                $cusInfo->city_code      = array_get($input, 'city_code', $cusInfo->city_code);
                $cusInfo->store_id       = TM::getCurrentStoreId();
                $cusInfo->district_code  = array_get($input, 'district_code', $cusInfo->district_code);
                $cusInfo->ward_code      = array_get($input, 'ward_code', $cusInfo->ward_code);
                $cusInfo->full_address   = array_get($input, 'address', $cusInfo->address);
                $cusInfo->street_address = array_get($input, 'address', $cusInfo->street_address);
                $cusInfo->note           = array_get($input, 'note', $cusInfo->note);
                $cusInfo->gender         = array_get($input, 'gender', $cusInfo->gender);
                $cusInfo->update();
            } else {
                CustomerInformation::insert(
                    [
                        'name'           => $input['name'] ?? null,
                        'phone'          => $input['phone'] ?? null,
                        'email'          => $input['email'] ?? null,
                        'address'        => $input['address'] ?? null,
                        'city_code'      => $input['city_code'] ?? null,
                        'store_id'       => TM::getCurrentStoreId(),
                        'district_code'  => $input['district_code'] ?? null,
                        'ward_code'      => $input['ward_code'] ?? null,
                        'full_address'   => $input['address'] ?? null,
                        'street_address' => $input['address'] ?? null,
                        'note'           => $input['note'] ?? null,
                        'gender'         => $input['gender'] ?? null,
                    ]
                );
            }
        }
        //Insert Distributor
        if (!empty($input['group_code'])) {
            if ($input['group_code'] === USER_GROUP_DISTRIBUTOR) {
                $distributor = Distributor::model()
                    ->where([
                        'code'          => $user->code,
                        'city_code'     => $input['city_code'],
                        'district_code' => $input['district_code'],
                        'ward_code'     => $input['ward_code'],
                        'company_id'    => TM::getCurrentCompanyId(),
                        'store_id'      => TM::getCurrentStoreId(),
                        'deleted'       => 0,
                    ])->first();
                // print_r(TM::getCurrentStoreId());die;
                if (!$distributor) {
                    $value = Distributor::model()->select('value')->orderBy('value', 'DESC')->first();
                    $value = $value->value ?? 0;
                    $value = $value + 1;
                    Distributor::insert([
                        'code'               => $user->code,
                        'name'               => $user->name,
                        'city_code'          => $input['city_code'] ?? null,
                        'city_full_name'     => $input['city_name'] ?? null,
                        'district_code'      => $input['district_code'] ?? null,
                        'district_full_name' => $input['district_name'] ?? null,
                        'ward_code'          => $input['ward_code'] ?? null,
                        'ward_full_name'     => $input['ward_name'] ?? null,
                        'company_id'         => TM::getCurrentCompanyId(),
                        'store_id'           => TM::getCurrentStoreId(),
                        'value'              => $value,
                        'is_active'          => 1,
                        'created_at'         => date('y-m-d H:i:s', time()),
                        'created_by'         => TM::getCurrentUserId(),
                    ]);
                    //GHN
                    // $district_id = District::model()->where('code', $input['district_code'])->first();
                    // $ward_code = Ward::model()->where('code', $input['ward_code'])->first();
                    // $city_name = City::model()->where('code', $input['city_code'])->first();
                    // $param2           = [
                    //     'district_id'   => (int)$district_id->code_ghn,
                    //     'ward_code'     => $ward_code->code_ghn,
                    //     'name'          => $user->name,
                    //     'phone'         => $user->phone,
                    //     'address'       => $input['address'],
                    // ];
                    // $client = new Client();
                    // $response = $client->post(env("GHN_END_POINT") . "/shiip/public-api/v2/shop/register", [
                    //     'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHN_TOKEN")],
                    //     'body'    => json_encode($param2),
                    // ]);
                    // //VTP
                    // $pr = [
                    //     'USERNAME'        => env("VTP_USERNAME"),
                    //     'PASSWORD'        => env("VTP_PASSWORD"),
                    // ];
                    // $client = new Client();
                    // $res = $client->post(env("VTP_END_POINT") . "/user/Login", [
                    //     'headers' => ['Content-Type' => 'application/json'],
                    //     'body'    => json_encode($pr),
                    // ]);
                    // $res = $res->getBody()->getContents() ?? null;
                    // $res = !empty($res) ? json_decode($res, true) : [];
                    // //getProvinces
                    // $rp = $client->get(env("VTP_END_POINT") . "/categories/listProvinceById?provinceId=",[
                    //     'headers' => ['Content-Type' => 'application/json', 'Token' => $res['data']['token']],
                    // ]);
                    // $rp = $rp->getBody()->getContents() ?? null;
                    // $provinces = json_decode($rp)->data;
                    // $fromProvinceID = $provinces[array_search($city_name->full_name,array_column($provinces, 'PROVINCE_NAME'))]->PROVINCE_ID;
                    // //getDistrict
                    // $rpd1 = $client->get(env("VTP_END_POINT") . "/categories/listDistrict?provinceId=" . $fromProvinceID,[
                    //     'headers' => ['Content-Type' => 'application/json', 'Token' => $res['data']['token']],
                    // ]);
                    // $rpd1 = $rpd1->getBody()->getContents() ?? null;
                    // $getFromDistricts = json_decode($rpd1)->data;
                    // $fromDistrict = $getFromDistricts[array_search(mb_strtoupper($district_id->full_name, "UTF-8"),array_column($getFromDistricts, 'DISTRICT_NAME'))]->DISTRICT_ID;
                    // //getWard
                    // $rpw = $client->get(env("VTP_END_POINT") . "/categories/listWards?districtId=" . $fromDistrict,[
                    //     'headers' => ['Content-Type' => 'application/json', 'Token' => $res['data']['token']],
                    // ]);
                    // $rpw = $rpw->getBody()->getContents() ?? null;
                    // $getFromWards = json_decode($rpw)->data;
                    // $fromWard = $getFromWards[array_search(mb_strtoupper($district_id->full_name,"UTF-8"),array_column($getFromWards, 'WARDS_NAME'))]->WARDS_ID;
                    // //create Store VTP
                    // $prvt = [
                    //     "PHONE"    =>$user->phone,
                    //     "NAME"     =>$user->name,
                    //     "ADDRESS"  =>$input['address'],
                    //     "WARDS_ID" =>$fromWard
                    // ];
                    // $rpcr = $client->post(env("VTP_END_POINT") . "/user/registerInventory",[
                    //     'headers' => ['Content-Type' => 'application/json', 'Token' => $res['data']['token']],
                    //     'body'    => json_encode($prvt)
                    // ]);      
                } else {
                    $distributor->code               = $user->code;
                    $distributor->name               = $user->name;
                    $distributor->city_code          = $input['city_code'] ?? $distributor->city_code;
                    $distributor->city_full_name     = $input['city_name'] ?? $distributor->city_full_name;
                    $distributor->district_code      = $input['district_code'] ?? $distributor->district_code;
                    $distributor->district_full_name = $input['district_name'] ?? $distributor->district_full_name;
                    $distributor->ward_code          = $input['ward_code'] ?? $distributor->ward_code;
                    $distributor->ward_full_name     = $input['ward_name'] ?? $distributor->ward_full_name;
                    $distributor->ward_full_name     = $input['ward_name'] ?? $distributor->ward_full_name;
                    $distributor->save();
                }
            }
            if ($input['group_code'] === USER_GROUP_DISTRIBUTOR_CENTER) {
                $distributorInCenter = Distributor::model()
                    ->where([
                        'code'          => $user->code,
                        'company_id'    => TM::getCurrentCompanyId(),
                        'store_id'      => TM::getCurrentStoreId(),
                        'deleted'       => 0,
                    ])->first();
                if (!$distributorInCenter) {
                    $value = Distributor::model()->select('value')->orderBy('value', 'DESC')->first();
                    $value = $value->value ?? 0;
                    $value = $value + 1;
                    Distributor::insert([
                        'code'               => $user->code,
                        'name'               => $user->name,
                        'city_code'          => $input['city_code'] ?? null,
                        'city_full_name'     => $input['city_name'] ?? null,
                        'district_code'      => $input['district_code'] ?? null,
                        'district_full_name' => $input['district_name'] ?? null,
                        'ward_code'          => $input['ward_code'] ?? null,
                        'ward_full_name'     => $input['ward_name'] ?? null,
                        'company_id'         => TM::getCurrentCompanyId(),
                        'store_id'           => TM::getCurrentStoreId(),
                        'value'              => $value,
                        'is_active'          => 1,
                        'created_at'         => date('y-m-d H:i:s', time()),
                        'created_by'         => TM::getCurrentUserId(),
                    ]);
                } else {
                    $distributorInCenter->code               = $user->code;
                    $distributorInCenter->name               = $user->name;
                    $distributorInCenter->city_code          = $input['city_code'] ?? $distributorInCenter->city_code;
                    $distributorInCenter->city_full_name     = $input['city_name'] ?? $distributorInCenter->city_full_name;
                    $distributorInCenter->district_code      = $input['district_code'] ?? $distributorInCenter->district_code;
                    $distributorInCenter->district_full_name = $input['district_name'] ?? $distributorInCenter->district_full_name;
                    $distributorInCenter->ward_code          = $input['ward_code'] ?? $distributorInCenter->ward_code;
                    $distributorInCenter->ward_full_name     = $input['ward_name'] ?? $distributorInCenter->ward_full_name;
                    $distributorInCenter->save();
                }
                $distributorCenter = DistributorCenter::model()
                    ->where([
                        'code'          => $user->code,
//                        'city_code'     => $input['city_code'],
//                        'district_code' => $input['district_code'],
//                        'ward_code'     => $input['ward_code'],
                        'company_id'    => TM::getCurrentCompanyId(),
                        'store_id'      => TM::getCurrentStoreId(),
                        'deleted'       => 0,
                    ])->first();
                if (!$distributorCenter) {
                    $value = DistributorCenter::model()->select('value')->orderBy('value', 'DESC')->first();
                    $value = $value->value ?? 0;
                    $value = $value + 1;
                    DistributorCenter::insert([
                        'code'               => $user->code,
                        'name'               => $user->name,
                        'city_code'          => $input['city_code'] ?? null,
                        'city_full_name'     => $input['city_name'] ?? null,
                        'district_code'      => $input['district_code'] ?? null,
                        'district_full_name' => $input['district_name'] ?? null,
                        'ward_code'          => $input['ward_code'] ?? null,
                        'ward_full_name'     => $input['ward_name'] ?? null,
                        'company_id'         => TM::getCurrentCompanyId(),
                        'store_id'           => TM::getCurrentStoreId(),
                        'value'              => $value,
                        'is_active'          => 1,
                        'created_at'         => date('y-m-d H:i:s', time()),
                        'created_by'         => TM::getCurrentUserId(),
                    ]);     
                } else {
                    $distributorCenter->code               = $user->code;
                    $distributorCenter->name               = $user->name;
                    $distributorCenter->city_code          = $input['city_code'] ?? $distributorCenter->city_code;
                    $distributorCenter->city_full_name     = $input['city_name'] ?? $distributorCenter->city_full_name;
                    $distributorCenter->district_code      = $input['district_code'] ?? $distributorCenter->district_code;
                    $distributorCenter->district_full_name = $input['district_name'] ?? $distributorCenter->district_full_name;
                    $distributorCenter->ward_code          = $input['ward_code'] ?? $distributorCenter->ward_code;
                    $distributorCenter->ward_full_name     = $input['ward_name'] ?? $distributorCenter->ward_full_name;
                    $distributorCenter->ward_full_name     = $input['ward_name'] ?? $distributorCenter->ward_full_name;
                    $distributorCenter->save();
                }
            }
        }
        $input['company_id'] = !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId();
        $this->updateCompanyStore($user, $input);
        $this->updateUserStore($user, $input);

        // Create || Upload ZoneHub
        if (!empty($userZoneHubs)) {
            $user->userZoneHub()->sync($userZoneHubs);
        }

        if (!empty($input['shippers'])) {
            $shippers = $input['shippers'];
            if ($id) {
                DistributorHasShipper::where('distributor_id', $id)->delete();
                foreach ($shippers as $shipper) {
                    $hasShipperModel                 = new DistributorHasShipper();
                    $hasShipperModel->distributor_id = $id;
                    $hasShipperModel->shipper_id     = $shipper['shipper_id'];
                    $hasShipperModel->shipper_code   = $shipper['shipper_code'];
                    $hasShipperModel->shipper_name   = $shipper['shipper_name'];
                    $hasShipperModel->save();
                }
            } else {
                foreach ($shippers as $shipper) {
                    $hasShipperModel                 = new DistributorHasShipper();
                    $hasShipperModel->distributor_id = $id;
                    $hasShipperModel->shipper_id     = $shipper['shipper_id'];
                    $hasShipperModel->shipper_code   = $shipper['shipper_code'];
                    $hasShipperModel->shipper_name   = $shipper['shipper_name'];
                    $hasShipperModel->save();
                }
            }
        }


        //        $this->updateCompanyStore($user, $input);

        //Create|Update Profile
        $profile = Profile::where(['user_id' => $user->id])->first();


        //        $y = date('Y', time());
        //        $m = date("m", time());
        //        $d = date("d", time());
        //        $dir = !empty($input['avatar']) ? "$y/$m/$d" : null;
        //        $file_name = empty($dir) ? null : "avatar_{$input['phone']}";
        //        if ($file_name) {
        //            $avatars = explode("base64,", $input['avatar']);
        //            $input['avatar'] = $avatars[1];
        //            if (!empty($file_name) && !is_image($avatars[1])) {
        //                return $this->response->errorBadRequest(Message::get("V002", "Avatar"));
        //            }
        //        }
        if (!empty($input['name'])) {
            $names = explode(" ", trim($input['name']));
            $first = $names[0];
            unset($names[0]);
            $last = !empty($names) ? implode(" ", $names) : null;
        }
        if (!empty($input['avatar'])) {
            $avatar = explode(",", $input['avatar']);
        }
        if (!empty($profile)) {
            $profile->user_id    = $user->id;
            $profile->email      = array_get($input, 'email', $profile->email);
            $profile->first_name = !empty($first) ? $first : $profile->first_name;
            $profile->last_name  = !empty($last) ? $last : $profile->last_name;
            $profile->short_name = !empty($input['name']) ? $input['name'] : $profile->short_name;
            $profile->full_name  = !empty($input['name']) ? $input['name'] : $profile->full_name;
            $profile->address    = array_get($input, 'address', $profile->address);
            $profile->phone      = array_get($input, 'phone', $profile->phone);
            $profile->birthday   = !empty($input['birthday']) ? date(
                'Y-m-d H:i:s',
                strtotime($input['birthday'])
            ) : $profile->birthday;
            $profile->gender     = array_get($input, 'gender', $profile->gender);
            //$profile->avatar = $file_name ? $dir . "/" . $file_name . ".jpg" : $profile->avatar;

            $profile->personal_verified = array_get($input, 'personal_verified', $profile->personal_verified);
            $profile->avatar            = $avatar[1] ?? $profile->avatar;
            $profile->city_code         = array_get($input, 'city_code', $profile->city_code);
            $profile->district_code     = array_get($input, 'district_code', $profile->district_code);
            $profile->ward_code         = array_get($input, 'ward_code', $profile->ward_code);
            $profile->id_number         = array_get($input, 'id_number', $profile->id_number);
            $profile->is_active         = array_get($input, 'is_active', $profile->is_active);
            $profile->indentity_card    = array_get($input, 'indentity_card', $profile->indentity_card);
            $profile->marital_status    = array_get($input, 'marital_status', $profile->marital_status);
            $profile->occupation        = array_get($input, 'occupation', $profile->occupation);
            $profile->education         = array_get($input, 'education', $profile->education);
            $profile->updated_by        = TM::getCurrentUserId();
            $profile->save();
        } else {
            $paramProfile = [
                'user_id'           => $user->id,
                'email'             => array_get($input, 'email'),
                'first_name'        => $first,
                'last_name'         => $last,
                'short_name'        => $input['name'],
                'full_name'         => $input['name'],
                'address'           => array_get($input, 'address', null),
                'phone'             => array_get($input, 'phone', null),
                'birthday'          => !empty($input['birthday']) ? date(
                    'Y-m-d H:i:s',
                    strtotime($input['birthday'])
                ) : null,
                'gender'            => array_get($input, 'gender', "O"),
                //'avatar'     => $file_name ? $dir . "/" . $file_name . ".jpg" : null,
                'avatar'            => $avatar[1] ?? null,
                'personal_verified' => array_get($input, 'personal_verified', 0),
                'city_code'         => array_get($input, 'city_code', null),
                'district_code'     => array_get($input, 'district_code', null),
                'ward_code'         => array_get($input, 'ward_code', null),
                'id_number'         => array_get($input, 'id_number', 0),
                'is_active'         => 1,
                'indentity_card'    => array_get($input, 'indentity_card', null),
                'marital_status'    => array_get($input, 'marital_status', null),
                'occupation'        => array_get($input, 'occupation', null),
                'education'         => array_get($input, 'education', null),
                'created_by'        => TM::getCurrentUserId(),
            ];

            $profileModel = new ProfileModel();
            $profileModel->create($paramProfile);
        }

        if (!empty($input['personal_images_upload'])) {
            $imgIds = [];
            foreach ($input['personal_images_upload'] as $base64) {
                $base64 = explode(';base64,', $base64);
                if (empty($base64[1])) {
                    throw new \Exception(Message::get("V002", "image"));
                }
                $img      = Image::uploadImage($base64[1]);
                $imgIds[] = $img->id;
            }

            if (!empty($imgIds)) {
                // Upload Image to Server
                $profile->personal_image_ids = implode(",", $imgIds);
                $profile->save();
            }

            // Remove Old Image

        }
        return $user;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        //        $this->sortBuilder($query, $input);
        if (!empty($input['group_code'])) {
            //            $query->whereHas('group', function ($q) use ($input) {
            $query->where('group_code', $input['group_code'])
                ->where('company_id', TM::getCurrentCompanyId());
            //            });
        }

        if (isset($input['is_active'])) {
            $query->where('users.is_active', $input['is_active']);
        }


        $profile_searchs = array_filter([
            'full_name'  => $input['full_name'] ?? null,
            'ready_work' => $input['ready_work'] ?? null,
        ]);
        foreach ($profile_searchs as $col => $value) {
            $query->where("profiles.$col", "like", "%$value%");
        }
        $full_columns = DB::getSchemaBuilder()->getColumnListing($this->getTable());
        $input        = array_intersect_key($input, array_flip($full_columns));
        $input        = array_filter($input);
        $query->leftJoin('profiles', 'profiles.user_id', '=', 'users.id');

        if (!empty($input['id'])) {
            $input['id'] = !is_array($input['id']) ? [$input['id']] : $input['id'];
            $query->whereIn('users.id', $input['id']);
            unset($input['id']);
        }
        //$input['is_active'] = isset($input['is_active']) ? $input['is_active'] : 0;

        foreach ($input as $col => $value) {
            //            if ($col == 'store_id' || $col == 'company_id') {
            if ($col == 'company_id') {
                continue;
            }
            if (isset($value)) {
                $query->where("users.$col", "like", "%$value%");
            }
        }

        $query->whereHas('userCompanies', function ($uc) {
            $uc->where('company_id', TM::getCurrentCompanyId());
        })->whereNull('users.deleted_at')
            ->whereNull('profiles.deleted_at');
        if (!empty($input['sort']['membership_rank'])) {
            $query->orderBy('ranking_id', $input['sort']['membership_rank']);
        }
        if (!empty($input['membership_rank'])) {
            $query->where('ranking_id', $input['membership_rank']);
        }
        if (!empty($input['store_id'])) {
            $query->where('users.store_id', $input['store_id']);
        }
        if (!empty($input['full_name'])) {
            $query->whereHas('profiles', function ($q) use ($input) {
                $q->where('full_name', 'like', "%{$input['full_name']}%");
            });
        }
        $query->groupBy('users.updated_at')->select('users.*');
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    public function searchStore($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        if (isset($input['is_active'])) {
            $query->where('users.is_active', $input['is_active']);
        }
        if (!empty($input['id'])) {
            $query->whereHas('userStores', function ($q) use ($input) {
                $q->where('store_id', $input['id']);
            });
        }
        if (!empty($input['name'])) {
            $query->whereHas('profiles', function ($q) use ($input) {
                $q->where('full_name', 'like', "%{$input['name']}%");
            });
        }
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }
    public function searchListPermission($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $isLogged = $input['is_logged'] ?? null;
        $query->where('users.is_active', $input['is_active'] ?? 1);
        if(!empty(TM::getCurrentStoreId())){
            $query->whereHas('userStores', function ($q) use($input){
                $q->where('store_id', TM::getCurrentStoreId());
            });
        }

//        if(!empty($input['is_active'])){
//            if($input['is_active']==1){
//                $query->where('is_active', 1);
//            }
//            if($input['is_active']==0){
//                $query->where('is_active', 0);
//                print_r($query->toSql());die;
//            }
//        }
        $roleCurrentGroup = TM::getCurrentRoleGroup();
        if ($roleCurrentGroup != USER_ROLE_GROUP_ADMIN){
            switch ($roleCurrentGroup) {
                case USER_ROLE_GROUP_MANAGER:
                    $distributorCode   = User::model()->where('distributor_center_code', TM::info()['code'])->pluck('code')->toArray();
                    $distributorCode[] = TM::info()['code'];
                    $query->where(function ($q3) use ($distributorCode) {
                        $q3->whereIn('users.code', $distributorCode);
                    });
                    break;
                case USER_ROLE_GROUP_EMPLOYEE:
                    if(TM::info()['role_code'] == USER_GROUP_DISTRIBUTOR){
                        $query->where(function ($q) {
                            $q->where([
                                'users.code' => TM::info()['code'],
                            ]);
                        });
                    }
                    if(TM::info()['role_code'] == USER_GROUP_HUB){
                        $query->where(function ($q) {
                            $q->where([
                                'users.code' => TM::info()['code'],
                            ]);
                        });
                    }
                    break;
            }
        }
        if (!empty($input['group_code'])) {
            $query->where('group_code', $input['group_code'])
                ->where('company_id', TM::getCurrentCompanyId());
        }
        if (!empty($input['role_code'])) {
            $roleCode = explode(',',$input['role_code']);
            $query->whereHas('role', function ($q) use ($roleCode) {
                $q->whereIn('code',$roleCode);
            });
            if($input['role_code'] == USER_ROLE_SELLER && TM::info()['role_code'] == USER_ROLE_LEADER){
                $query->where('parent_id',TM::info()['id']);
            }
        }

        $profile_searchs = array_filter([
            'full_name' => $input['full_name'] ?? null,
        ]);

        foreach ($profile_searchs as $col => $value) {
            $query->where("profiles.$col", "like", "%$value%");
        }

        $full_columns = DB::getSchemaBuilder()->getColumnListing($this->getTable());
        $input        = array_intersect_key($input, array_flip($full_columns));
        $input        = array_filter($input);
        $query->leftJoin('profiles', 'profiles.user_id', '=', 'users.id');

        if (!empty($input['id'])) {
            $input['id'] = !is_array($input['id']) ? [$input['id']] : $input['id'];
            $query->whereIn('users.id', $input['id']);
            unset($input['id']);
        }

        if (isset($isLogged)) {
            $input['is_logged']  = $isLogged;
            $input['group_code'] = USER_GROUP_OUTLET;
        }
        foreach ($input as $col => $value) {
            //            if ($col == 'store_id' || $col == 'company_id') {
            if ($col == 'company_id') {
                continue;
            }
            if (isset($value)) {
                $query->where("users.$col", "like", "%$value%");
            }
        }
        // $query->whereHas('userCompanies', function ($uc) {
        //     $uc->where('company_id', TM::getCurrentCompanyId());
        // })
        $query->whereNull('users.deleted_at')
            ->whereNull('profiles.deleted_at');
        if (!empty($input['sort']['membership_rank'])) {
            $query->orderBy('ranking_id', $input['sort']['membership_rank']);
        }
        //        if (!empty($input['membership_rank'])) {
        //            $query->where('ranking_id', $input['membership_rank']);
        //        }
        //        if (!empty($input['store_id'])) {
        //            $query->where('users.store_id', $input['store_id']);
        //        }
        if (!empty($input['full_name'])) {
            $query->whereHas('profiles', function ($q) use ($input) {
                $q->where('full_name', 'like', "%{$input['full_name']}%");
            });
        }
        $query->select('users.*');

        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }
    public function searchList($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $input['sort']['users.updated_at']='desc';
        $query->orderBy('users.updated_at', $input['sort']['users.updated_at']);
        $this->sortBuilder($query, $input);
        $isLogged = $input['is_logged'] ?? null;
        $query->where('users.is_active', $input['is_active'] ?? 1);
        if(!empty(TM::getCurrentStoreId())){
            $query->whereHas('userStores', function ($q) use($input){
                $q->where('store_id', TM::getCurrentStoreId());
            });
        }
        $query->whereNull('users.deleted_at')
             ->whereNull('profiles.deleted_at');
//        if(!empty($input['is_active'])){
//            if($input['is_active']==1){
//                $query->where('is_active', 1);
//            }
//            if($input['is_active']==0){
//                $query->where('is_active', 0);
//                print_r($query->toSql());die;
//            }
//        }
        $roleLevel = TM::info()['role_level'];
        if(TM::info()['role_code'] != USER_ROLE_SUPERADMIN) {
            $query->whereHas('role', function ($q) use ($roleLevel) {
                $q->where('role_level','>=',$roleLevel);
            });
        }
        if (!empty($input['group_code'])) {
            $query->where('group_code', $input['group_code'])
                ->where('company_id', TM::getCurrentCompanyId());
        }
        if (!empty($input['role_code'])) {
            $roleCode = explode(',',$input['role_code']);
            $query->whereHas('role', function ($q) use ($roleCode) {
                $q->whereIn('code',$roleCode);
            });
            if($input['role_code'] == USER_ROLE_SELLER && TM::info()['role_code'] == USER_ROLE_LEADER){
                $query->where('parent_id',TM::info()['id']);
            }
        }

        $profile_searchs = array_filter([
            'full_name' => $input['full_name'] ?? null,
        ]);

        foreach ($profile_searchs as $col => $value) {
            $query->where("users.name", "like", "%$value%");
        }

        $full_columns = DB::getSchemaBuilder()->getColumnListing($this->getTable());
        $input        = array_intersect_key($input, array_flip($full_columns));
        $input        = array_filter($input);
        $query->leftJoin('profiles', 'profiles.user_id', '=', 'users.id');

        if (!empty($input['id'])) {
            $input['id'] = !is_array($input['id']) ? [$input['id']] : $input['id'];
            $query->whereIn('users.id', $input['id']);
            unset($input['id']);
        }

        if (isset($isLogged)) {
            $input['is_logged']  = $isLogged;
            $input['group_code'] = USER_GROUP_OUTLET;
        }
        foreach ($input as $col => $value) {
            //            if ($col == 'store_id' || $col == 'company_id') {
            if ($col == 'company_id') {
                continue;
            }
            if (isset($value)) {
                $query->where("users.$col", "like", "%$value%");
            }
        }
        // $query->whereHas('userCompanies', function ($uc) {
        //     $uc->where('company_id', TM::getCurrentCompanyId());
        // })
        if (!empty($input['sort']['membership_rank'])) {
            $query->orderBy('ranking_id', $input['sort']['membership_rank']);
        }
        //        if (!empty($input['membership_rank'])) {
        //            $query->where('ranking_id', $input['membership_rank']);
        //        }
        //        if (!empty($input['store_id'])) {
        //            $query->where('users.store_id', $input['store_id']);
        //        }
        // if (!empty($input['full_name'])) {
        //     $query->whereHas('profiles', function ($q) use ($input) {
        //         $q->where('full_name', 'like', "%{$input['full_name']}%");
        //     });
        // }
        $query->select('users.*');

        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    //    private function updateCompanyStore(User $user, $input)
    //    {
    //        // Delete Old Company
    //        UserCompany::model()->where('user_id', $user->id)->delete();
    //        // Delete Old Store
    //        UserStore::model()->where('user_id', $user->id)->delete();
    //        $roles = Role::model()->get()->pluck(null, 'id')->toArray();
    //
    //        $companies = Company::model()->whereIn('id', array_column($input['companies'], 'company_id'))
    //            ->get()->pluck(null, 'id')->toArray();
    //
    //        $stores = Store::model()->selectRaw("GROUP_CONCAT(`id`) as store_ids, company_id")
    //            ->whereNotNull('company_id')->where('company_id', '!=', '')
    //            ->groupBy('company_id')->get()
    //            ->each(function ($query) {
    //                $query->store_ids = explode(",", $query->store_ids);
    //            })
    //            ->pluck('store_ids', 'company_id')->toArray();
    //
    //        $allStore = Store::model()->whereNotNull('company_id')->where('company_id', '!=', '')->get()
    //            ->pluck(null, 'id')->toArray();
    //
    //        // Create New Company
    //        $paramCompany = [];
    //        $paramStore = [];
    //        foreach ($input['companies'] as $company) {
    //            $time = date('Y-m-d H:i:s', time());
    //            $paramCompany[] = [
    //                'user_id'      => $user->id,
    //                'user_code'    => $user->code,
    //                'user_name'    => object_get($user, 'profile.full_name', $input['name']),
    //                'company_id'   => $company['company_id'],
    //                'company_code' => $companies[$company['company_id']]['code'],
    //                'company_name' => $companies[$company['company_id']]['name'],
    //                'role_id'      => $company['role_id'],
    //                'role_code'    => $roles[$company['role_id']]['code'],
    //                'role_name'    => $roles[$company['role_id']]['name'],
    //                'created_at'   => $time,
    //                'created_by'   => TM::getCurrentUserId(),
    //                'updated_at'   => $time,
    //                'updated_by'   => TM::getCurrentUserId(),
    //            ];
    //
    //            // Create New Store
    //            foreach ($input['stores'] as $store) {
    //                if (empty($stores[$company['company_id']]) ||
    //                    !in_array($store['store_id'], $stores[$company['company_id']])
    //                ) {
    //                    continue;
    //                }
    //
    //                $paramStore[] = [
    //                    'user_id'      => $user->id,
    //                    'user_code'    => $user->code,
    //                    'user_name'    => object_get($user, 'profile.full_name', $input['name']),
    //                    'company_id'   => $company['company_id'],
    //                    'company_code' => $companies[$company['company_id']]['code'],
    //                    'company_name' => $companies[$company['company_id']]['name'],
    //                    'store_id'     => $store['store_id'],
    //                    'store_code'   => $allStore[$store['store_id']]['code'],
    //                    'store_name'   => $allStore[$store['store_id']]['name'],
    //                    'role_id'      => $store['role_id'],
    //                    'role_code'    => $roles[$store['role_id']]['code'],
    //                    'role_name'    => $roles[$store['role_id']]['name'],
    //                    'created_at'   => $time,
    //                    'created_by'   => TM::getCurrentUserId(),
    //                    'updated_at'   => $time,
    //                    'updated_by'   => TM::getCurrentUserId(),
    //                ];
    //            }
    //        }
    //
    //        if ($paramCompany) {
    //            UserCompany::insert($paramCompany);
    //        }
    //
    //        if ($paramStore) {
    //            UserStore::insert($paramStore);
    //        }
    //
    //        return true;
    //    }

    private function updateCompanyStore(User $user, $input)
    {
        // Delete Old Company
        // UserCompany::model()->where('user_id', $user->id)->delete();
        $role    = Role::model()->where('id', $user->role_id)->first();
        $company = Company::model()->where('id', $input['company_id'])->first();

        $userCompany = UserCompany::model()->where(['user_id' => $user->id, 'company_id' => $company->id]);

        // Create New Company
        $time         = date('Y-m-d H:i:s', time());
        $paramCompany = [
            'user_id'      => $user->id,
            'user_code'    => $user->code,
            'user_name'    => object_get($user, 'profile.full_name', $input['name']),
            'company_id'   => $company->id,
            'company_code' => $company->code,
            'company_name' => $company->name,
            'role_id'      => $role->id,
            'role_code'    => $role->code,
            'role_name'    => $role->name,
            'created_at'   => $time,
            'created_by'   => TM::getCurrentUserId(),
            'updated_at'   => $time,
            'updated_by'   => TM::getCurrentUserId(),
        ];

        UserCompany::insert($paramCompany);
    }

    private function updateUserStore(User $user, $input)
    {
        $role    = Role::model()->select(['id', 'code', 'name'])->where('id', $user->role_id)->first()->toArray();
        $store   = Store::model()->select(['id', 'code', 'name'])->where('id', !empty($input['store_id']) ? $input['store_id'] : TM::getCurrentStoreId())->first()->toArray();
        $company = Company::model()->select(['id', 'code', 'name'])->where('id', $input['company_id'])->first()->toArray();

        // Create New UserStore
        $time           = date('Y-m-d H:i:s', time());
        $paramUserStore = [
            'user_id'      => $user->id,
            'role_id'      => $role['id'],
            'company_id'   => $company['id'],
            'store_id'     => $store['id'],
            'user_code'    => $user->code,
            'user_name'    => object_get($user, 'profile.full_name', $input['name']),
            'store_code'   => $store['code'],
            'store_name'   => $store['name'],
            'company_code' => $company['code'],
            'company_name' => $company['name'],
            'role_code'    => $role['code'],
            'role_name'    => $role['name'],
            'created_at'   => $time,
            'created_by'   => TM::getCurrentUserId(),
            'updated_at'   => $time,
            'updated_by'   => TM::getCurrentUserId(),
        ];

        UserStore::insert($paramUserStore);
    }

    public function searchByPhone($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (!empty($input['phone'])) {
            $query->where('phone', '=', $input['phone']);
        }
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    public function findDistributor(
        int $customer_id,
        string $city_code = null,
        string $district_code = null,
        string $ward_code = null,
        array $denied_ids = null
    )
    {
        $customer = User::model()->where(['id' => $customer_id, 'type' => USER_TYPE_CUSTOMER])->first();
        if (!$customer) {
            return null;
        }

        // Get Own Distributor
        if (!empty($customer->distributor_id)) {
            $own_distributor = User::model()->select([
                'users.id',
                'users.code',
                'users.name',
                'users.email',
                'users.phone',
                'p.city_code',
                'p.district_code',
                'p.ward_code'
            ])->join('profiles as p', 'p.user_id', '=', 'users.id')
                ->where([
                    'company_id' => $customer->company_id,
                    'group_code' => USER_GROUP_DISTRIBUTOR,
                    'users.id'   => $customer->distributor_id
                ])->first();

            if (!$denied_ids || !in_array($customer->distributor_id, $denied_ids)) {
                return $own_distributor;
            }
        }

        $distributors = User::model()->select([
            'users.id',
            'users.code',
            'users.name',
            'users.email',
            'users.phone',
            'p.city_code',
            'p.district_code',
            'p.ward_code'
        ])->join('profiles as p', 'p.user_id', '=', 'users.id')
            ->where('company_id', $customer->company_id)
            ->where('group_code', USER_GROUP_DISTRIBUTOR);
        if ($denied_ids) {
            $distributors = $distributors->whereNotIn('users.id', $denied_ids);
        }
        $distributors = $distributors->get()->toArray();

        if (empty($distributors)) {
            return null;
        }

        // Find by Ward
        $key = array_search($ward_code, array_column($distributors, 'ward_code'));

        // Find by District
        if (empty($key)) {
            $key = array_search($district_code, array_column($distributors, 'district_code'));
        }

        // Find by City
        if (empty($key)) {
            $key = array_search($city_code, array_column($distributors, 'city_code'));
        }

        $key = (int)$key;

        return $distributors[$key] ?? null;
    }

    public function findDistributor2(
        int $customer_id,
        string $city_code = null,
        string $district_code = null,
        string $ward_code = null,
        array $denied_ids = null
    )
    {
        $customer = User::model()->where(['id' => $customer_id, 'type' => USER_TYPE_CUSTOMER])->first();
        if (!$customer) {
            return null;
        }
        // Get Own Distributor
        if (!empty($customer->distributor_id)) {
            $own_distributor = User::model()->select([
                'users.id',
                'users.code',
                'users.name',
                'users.email',
                'users.phone',
                'p.city_code',
                'p.district_code',
                'p.ward_code'
            ])->join('profiles as p', 'p.user_id', '=', 'users.id')
                ->join('user_stores as us', 'us.user_id', '=', 'users.id')
                ->where([
                    'users.group_code' => USER_GROUP_DISTRIBUTOR,
                    'users.id'         => $customer->distributor_id,
                    'us.company_id'    => $customer->company_id,
                    'us.store_id'      => TM::getCurrentStoreId()
                ])->first();

            if (!$denied_ids || !in_array($customer->distributor_id, $denied_ids)) {
                return $own_distributor;
            }
        }

        $distributors = Distributor::model()->select([
            'id',
            'code',
            'name',
            'value',
            'city_code',
            'district_code',
            'ward_code'
        ])->where('company_id', $customer->company_id)
            ->where('store_id', $customer->store_id);

        // $distributors = User::model()->select([
        //     'users.id as id',
        //     'users.code as code',
        //     'users.name as name',
        //     'p.city_code as city_code',
        //     'p.district_code as district_code',
        //     'p.ward_code as ward_code'
        // ])
        //     ->join('profiles as p', 'p.user_id', '=', 'users.id')
        //     ->join('user_stores as us', 'us.user_id', '=', 'users.id')
        //     ->where('us.store_id', TM::getCurrentStoreId())
        //     ->where('users.company_id', TM::getCurrentCompanyId())
        //     ->where('users.group_code', USER_GROUP_DISTRIBUTOR);

        // if ($denied_ids) {
        //     $distributors = $distributors->whereNotIn('users.id', $denied_ids);
        // }
        $distributors = $distributors->get()->toArray();

        if (empty($distributors)) {
            return null;
        }

        // Find by Ward
        $key = array_search($ward_code, array_column($distributors, 'ward_code'));

        // Find by District
        if (empty($key)) {
            $key = array_search($district_code, array_column($distributors, 'district_code'));
        }

        // Find by City
        if (empty($key)) {
            $key = array_search($city_code, array_column($distributors, 'city_code'));
        }

        $key = (int)$key;
        if (!$key) {
            return null;
        }
        return $distributors[$key] ?? null;
    }

    /**
     * @param User $user
     * @return User|null
     */
    public function getUserLevel1FromUser(User $user)
    {
        if (empty($user->level_number)) {
            return null;
        }

        if ($user->level_number == 1) {
            return $user;
        }

        if ($user->level_number == 3) {
            $user = User::model()->where('id', $user->user_level2_ids)->first();
            if (empty($user)) {
                return null;
            }
        }

        $userLevel1 = User::model()->where('id', $user->user_level1_id)->first();

        return $userLevel1;
    }

    /**
     * @param User $userLevel1
     * @param array $options
     * @return User|array
     */
    public function getUserTree($myId, User $userLevel1, $options = ['*'])
    {
        if (empty($userLevel1)) {
            return [];
        }

        $input['user_ids'] = explode(
            ",",
            $userLevel1->id . "," . $userLevel1->user_level1_id . "," . $userLevel1->user_level2_ids . "," . $userLevel1->user_level3_ids
        );
        $input['user_ids'] = array_unique(array_filter($input['user_ids']));

        $input['from'] = '2020-11-01'; //date("Y-m-01");
        $input['to']   = date('Y-m-d');

        // Get Setting 1
        $settingModel       = new SettingModel();
        $settingCommission1 = $settingModel->getDataForKey('CUSTOMER-COMMISSIONS', 'key');

        // Get Setting 2
        $settingModel       = new SettingModel();
        $settingCommission2 = $settingModel->getForKey('CUSTOMER-REFERRAL-COMMISSIONS', 'key');

        // Get Order
        $orderModel          = new OrderModel();
        $allCategory         = $orderModel->getAllCategoryOrder($input);
        $allCategoryInOrders = array_pluck($allCategory, 'product_category');
        $cate                = [];
        if (!empty($allCategoryInOrders)) {
            foreach ($allCategoryInOrders as $categoryInOrder) {
                $cat = Category::find($categoryInOrder);
                if (!empty($cat->data)) {
                    $cate[] = $categoryInOrder;
                }
            }
        }
        if (!empty($cate)) {
            $input['category_ids'] = $cate;
            $orders                = $orderModel->getTotalSalesForCustomerByCategory($input);
        } else {
            $orders = $orderModel->getTotalSalesForCustomers($input);
        }

        if (!empty($orders) && !empty($cate)) {
            $orders = $orderModel->calculateForTree($orders, $settingCommission1, $settingCommission2, $input['from'], $input['to']);
        }

        if (!empty($orders) && empty($cate)) {
            $orders = $orderModel->calculateForTreeNonCate($orders, $settingCommission1, $settingCommission2);
        }

        $userOrder = [];
        foreach ($orders as $order) {
            $userOrder[$order['customer_id']] = $order;
        }

        $userLevel1 = $userLevel1->toArray();
        $userLevel1 = $options == ['*'] ? $userLevel1 : array_intersect_key($userLevel1, array_flip($options));

        $userLevel2s = User::model()->select($options)
            ->whereIn('id', explode(",", $userLevel1['user_level2_ids']))->get()->toArray();
        if (!empty($userLevel1['user_level2_ids'])) {
            foreach ($userLevel2s as $key => $userLevel2) {
                $userLevel3s = [];
                if (!empty($userLevel2['user_level3_ids'])) {
                    $userLevel3s = User::model()->select($options)
                        ->whereIn('id', explode(",", $userLevel2['user_level3_ids']))->get()->toArray();
                    $userLevel3s = array_map(function ($u) use ($userOrder, $myId, $userLevel1, $userLevel2) {
                        $u['commission'] = in_array($myId, array_filter([
                            $userLevel1['id'],
                            $userLevel2['id'],
                            $u['id']
                        ])) ? ($userOrder[$u['id']] ?? null) : null;
                        return $u;
                    }, $userLevel3s);
                }
                $userLevel2s[$key]['children']   = $userLevel3s;
                $userLevel2s[$key]['commission'] = in_array($myId, array_filter([
                    $userLevel1['id'],
                    $userLevel2['id']
                ])) ? ($userOrder[$userLevel2['id']] ?? null) : null;
            }
        }
        $userLevel1['children']   = $userLevel2s;
        $userLevel1['commission'] = $myId == $userLevel1['id'] ? ($userOrder[$userLevel1['id']] ?? null) : null;

        return $userLevel1;
    }
}

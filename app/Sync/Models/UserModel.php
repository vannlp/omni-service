<?php
/**
 * User: dai.ho
 * Date: 28/01/2021
 * Time: 3:21 PM
 */

namespace App\Sync\Models;


use App\City;
use App\Company;
use App\Distributor;
use App\District;
use App\Image;
use App\Profile;
use App\Role;
use App\Supports\Message;
use App\TM;
use App\User;
use App\UserCompany;
use App\UserGroup;
use App\V1\Models\AbstractModel;
use App\V1\Models\ProfileModel;
use App\Ward;
use App\ZoneHub;
use Illuminate\Support\Arr;
use phpDocumentor\Reflection\Type;

/**
 * Class UserModel
 * @package App\Sync\Models
 */
class UserModel extends AbstractModel
{
    /**
     * UserModel constructor.
     * @param User|null $model
     */
    public function __construct(User $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input, $isUpdate = false, $type = USER_GROUP_DISTRIBUTOR)
    {
        $allRole = Role::model()->pluck('id', 'code')->toArray();
        $phone   = "";
        if (!empty($input['Phone'])) {
            $phone = str_replace(" ", "", $input['Phone']);
            $phone = preg_replace('/\D/', '', $phone);
        }

        $input['CustomerCode'] = string_to_slug($input['CustomerCode']);
        $input['CustomerName'] = !empty($input['CustomerName']) ? $input['CustomerName'] : $input['CustomerCode'];

        if (!empty($input['zone_hub_ids'])) {
            $userZoneHubs = $input['zone_hub_ids'] ?? [];
            $zone_hub_ids = implode(",", $userZoneHubs);
            foreach ($userZoneHubs as $userZoneHub) {
                $zoneHub = ZoneHub::where([
                    'company_id' => TM::getIDP()->company_id,
                    'id'         => $userZoneHub,
                ])->first();
                if (empty($zoneHub)) {
                    throw new \Exception(Message::get("V003", "ID Zone Hub: #$userZoneHub"));
                }
            }
        }

        if (!empty($input['reference_phone'])) {
            $checkPhone = User::model()->where('phone', $input['reference_phone'])
                ->whereHas('userStores', function ($q) {
                    $q->where('store_id', TM::getIDP()->store_id);
                })->first();
            if (empty($checkPhone)) {
                throw new \Exception(Message::get("V003", Message::get("reference_phone")));
            }
        }

        $userGroup = UserGroup::model()->where([
            'code'       => $type,
            'company_id' => TM::getIDP()->company_id,
        ])->first();

        $distributor = null;
        if (!empty($input['ShopCode'])) {
            $distributor = User::model()->where([
                'code'       => $input['ShopCode'],
                'company_id' => TM::getIDP()->company_id,
                'group_code' => USER_GROUP_DISTRIBUTOR,
            ])->first();
        }
        if ($isUpdate) {
            $user = User::model()->where([
                'code'       => $input['CustomerCode'],
                'company_id' => TM::getIDP()->company_id,
                'store_id'   => TM::getIDP()->store_id,
            ])->first();
            if (empty($user)) {
                throw new \Exception(Message::get("V003", "Code: #{$input['CustomerCode']}"));
            }

            $user->phone     = !empty($phone) ? $phone : $user->phone;
            $user->code      = $input['CustomerCode'];
            $user->name      = !empty($input['CustomerName']) ? $input['CustomerName'] : null;
            $user->email     = array_get($input, 'Email', null);
            $user->role_id   = $allRole[USER_ROLE_GUEST];
            $user->data_sync = json_encode($input);

            $user->distributor_id   = $distributor->id ?? null;
            $user->distributor_code = $distributor->code ?? null;
            $user->distributor_name = $distributor->name ?? null;

            $user->type         = USER_TYPE_CUSTOMER;
            $user->zone_hub_ids = $zone_hub_ids ?? null;
            $user->verify_code  = mt_rand(100000, 999999);
            $user->expired_code = date('Y-m-d H:i:s', strtotime("+5 minutes"));
            $user->tax          = array_get($input, 'InvoiceTax');
            $user->is_active    = array_get($input, 'is_active', 1);
            $user->group_id     = $userGroup->id ?? null;
            $user->group_code   = $userGroup->code ?? null;
            $user->group_name   = $userGroup->name ?? null;
            $user->is_partner   = 0;
            $user->company_id   = TM::getIDP()->company_id;
            $user->updated_by   = TM::getIDP()->sync_name;
            $user->save();
        } else {
            $param = [
                'phone'            => $phone,
                'code'             => $input['CustomerCode'],
                'name'             => !empty($input['CustomerName']) ? $input['CustomerName'] : null,
                'email'            => array_get($input, 'email'),
                'role_id'          => $allRole[USER_ROLE_GUEST],
                'data_sync'        => json_encode($input),
                'type'             => USER_TYPE_CUSTOMER,
                'zone_hub_ids'     => $zone_hub_ids ?? null,
                'verify_code'      => mt_rand(100000, 999999),
                'expired_code'     => date('Y-m-d H:i:s', strtotime("+5 minutes")),
                'register_at'      => date('Y-m-d H:i:s'),
                'group_id'         => $userGroup->id ?? null,
                'group_code'       => $userGroup->code ?? null,
                'group_name'       => $userGroup->name ?? null,
                'is_partner'       => 0,
                'distributor_id'   => $distributor->id ?? null,
                'distributor_code' => $distributor->code ?? null,
                'distributor_name' => $distributor->name ?? null,
                'tax'              => $input['InvoiceTax'] ?? null,
                'is_active'        => 1,
                'store_id'         => TM::getIDP()->store_id,
                'company_id'       => TM::getIDP()->company_id,
                'created_by'       => TM::getIDP()->sync_name,
            ];
            $user  = $this->create($param);
        }

        $input['company_id'] = TM::getIDP()->company_id;
        $this->updateCompanyStore($user, $input);

        // Create || Upload ZoneHub
        if (!empty($userZoneHubs)) {
            $user->userZoneHub()->sync($userZoneHubs);
        }

        $profile = Profile::where(['user_id' => $user->id])->first();
        if (!empty($input['CustomerName'])) {
            $names = explode(" ", trim($input['CustomerName']));
            $first = $names[0];
            unset($names[0]);
            $last = !empty($names) ? implode(" ", $names) : null;
        }
        $city              = City::model()->where('province', $input['Province'])->first();
        $input['Province'] = Arr::get($city, 'code', null);
        $district          = District::model()->where('district', $input['District'])->first();
        $input['District'] = Arr::get($district, 'code', null);
        $ward              = Ward::model()->where('name', $input['Ward'])->first();
        $input['Ward']     = Arr::get($ward, 'code', null);

        if (!empty($profile)) {
            $profile->user_id        = $user->id;
            $profile->email          = array_get($input, 'Email', $profile->email);
            $profile->first_name     = !empty($first) ? $first : $profile->first_name;
            $profile->last_name      = !empty($last) ? $last : $profile->last_name;
            $profile->short_name     = !empty($input['CustomerName']) ? $input['CustomerName'] : $profile->short_name;
            $profile->full_name      = !empty($input['CustomerName']) ? $input['CustomerName'] : $profile->full_name;
            $profile->address        = array_get($input, 'Address', $profile->address);
            $profile->phone          = array_get($input, 'Phone', $profile->phone);
            $profile->gender         = "O";
            $profile->lat            = $input['Lat'];
            $profile->long           = $input['Lng'];
            $profile->avatar         = $avatar[1] ?? $profile->avatar;
            $profile->city_code      = array_get($input, 'Province', null);
            $profile->district_code  = array_get($input, 'District');
            $profile->ward_code      = array_get($input, 'Ward');
            $profile->id_number      = array_get($input, 'Indo');
            $profile->is_active      = 1;
            $profile->indentity_card = array_get($input, 'Indo');
            $profile->updated_by     = TM::getIDP()->sync_name;
            $profile->save();
        } else {
            $paramProfile = [
                'user_id'           => $user->id,
                'email'             => array_get($input, 'Email'),
                'first_name'        => $first,
                'last_name'         => $last,
                'short_name'        => $input['CustomerName'],
                'full_name'         => $input['CustomerName'],
                'address'           => array_get($input, 'Address', null),
                'phone'             => array_get($input, 'Phone', null),
                'gender'            => "O",
                'lat'               => $input['Lat'],
                'long'              => $input['Lng'],
                'avatar'            => $avatar[1] ?? null,
                'personal_verified' => array_get($input, 'personal_verified', 0),
                'city_code'         => array_get($input, 'Province', null),
                'district_code'     => array_get($input, 'District', null),
                'ward_code'         => array_get($input, 'Ward', null),
                'id_number'         => array_get($input, 'Indo', 0),
                'is_active'         => 1,
                'indentity_card'    => array_get($input, 'Indo', null),
                'created_by'        => TM::getIDP()->sync_name,
            ];
            $profileModel = new ProfileModel();
            $profileModel->create($paramProfile);
        }

        //Insert Distributor
        if ($userGroup->code === USER_GROUP_DISTRIBUTOR) {
            $value = Distributor::model()->select('value')->orderBy('value', 'DESC')->first();
            Distributor::insert([
                'code'               => $user->code,
                'name'               => $user->name,
                'city_code'          => array_get($input, 'Province', null),
                'city_full_name'     => $city->full_name ?? null,
                'district_code'      => array_get($input, 'District', null),
                'district_full_name' => $district->full_name ?? null,
                'ward_code'          => array_get($input, 'Ward', null),
                'ward_full_name'     => $ward->full_name ?? null,
                'value'              => $value->value ?? 0 + 1,
                'is_active'          => 1,
                'store_id'           => TM::getIDP()->store_id,
                'company_id'         => TM::getIDP()->company_id,
                'created_at'         => date('y-m-d H:i:s', time()),
                'created_by'         => TM::getIDP()->sync_name,
            ]);
        }

        return $user;
    }

    private function updateCompanyStore(User $user, $input)
    {
        // Delete Old Company
        $role    = Role::model()->where('id', $user->role_id)->first();
        $company = Company::model()->where('id', $input['company_id'])->first();

        // Create New Company
        $time         = date('Y-m-d H:i:s', time());
        $paramCompany = [
            'user_id'      => $user->id,
            'user_code'    => $user->code,
            'user_name'    => object_get($user, 'profile.full_name', $input['CustomerName']),
            'company_id'   => $company->id,
            'company_code' => $company->code,
            'company_name' => $company->name,
            'role_id'      => $role->id,
            'role_code'    => $role->code,
            'role_name'    => $role->name,
            'created_at'   => $time,
            'created_by'   => TM::getIDP()->sync_name,
            'updated_at'   => $time,
            'updated_by'   => TM::getIDP()->sync_name,
        ];

        UserCompany::insert($paramCompany);
    }
}
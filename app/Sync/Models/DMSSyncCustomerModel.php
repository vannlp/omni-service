<?php


namespace App\Sync\Models;


use App\Distributor;
use App\DMSSyncCustomer;

//use App\UserCustomer;
use App\Profile;
use App\Role;
use App\Supports\Message;
use App\TM;
use App\User;
use App\UserGroup;
use App\V1\Models\AbstractModel;
use App\V1\Models\ProfileModel;
use DateTime;

class DMSSyncCustomerModel extends AbstractModel
{
    public function __construct(DMSSyncCustomer $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input, $isUpdate = false, $type = USER_GROUP_DISTRIBUTOR)
    {
        $param = [
            'customer_id'              => $input["CUSTOMER_ID"] ?? "",
            'shop_id'                  => $input["SHOP_ID"] ?? "",
            'code'                     => $input["CUSTOMER_CODE"] ?? "",
            'short_code'               => $input["SHORT_CODE"] ?? "",
            'first_code'               => $input["FIRST_CODE"] ?? "",
            'name'                     => $input["CUSTOMER_NAME"] ?? "",
            'contact_name'             => $input["CONTACT_NAME"] ?? "",
            'phone'                    => $input["PHONE"] ?? null,
            'mobiphone'                => $input["MOBIPHONE"] ?? null,
            'email'                    => $input["EMAIL"] ?? "",
            'channel_type_id'          => $input["CHANNEL_TYPE_ID"] ?? null,
            'max_debit_amount'         => $input["MAX_DEBIT_AMOUNT"] ?? "",
            'max_debit_date'           => !empty($input["MAX_DEBIT_DATE"]) ? date("Y-m-d", strtotime($input["MAX_DEBIT_DATE"])) : null,
            'area_id'                  => $input["AREA_ID"] ?? null,
            'house_number'             => $input["HOUSENUMBER"] ?? "",
            'street'                   => $input["STREET"] ?? "",
            'address'                  => $input["ADDRESS"] ?? "",
            'region'                   => $input["REGION"] ?? "",
            'image_url'                => $input["IMAGE_URL"] ?? "",
            'last_approve_order'       => !empty($input["LAST_APPROVE_ORDER"]) ? (DateTime::createFromFormat('d/m/Y H.i.s', $input["LAST_APPROVE_ORDER"])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($input["LAST_APPROVE_ORDER"]))) : null,
            'last_order'               => !empty($input["LAST_ORDER"]) ? (DateTime::createFromFormat('d/m/Y H.i.s', $input["LAST_ORDER"])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($input["LAST_ORDER"]))) : null,
            'status'                   => $input["STATUS"] ?? null,
            'invoice_company_name'     => $input["INVOICE_CONPANY_NAME"] ?? "",
            'invoice_outlet_name'      => $input["INVOICE_OUTLET_NAME"] ?? "",
            'invoice_tax'              => $input["INVOICE_TAX"] ?? "",
            'invoice_payment_type'     => $input["INVOICE_PAYMENT_TYPE"] ?? "",
            'invoice_number_account'   => $input["INVOICE_NUMBER_ACCOUNT"] ?? "",
            'invoice_name_bank'        => $input["INVOICE_NAME_BANK"] ?? "",
            'delivery_address'         => $input["DELIVERY_ADDRESS"] ?? "",
            'lat'                      => $input["LAT"] ?? null,
            'name_text'                => $input["NAME_TEXT"] ?? "",
            'apply_debit_limited'      => $input["APPLY_DEBIT_LIMITED"] ?? "",
            'idno'                     => $input["IDNO"] ?? null,
            'lng'                      => $input["LNG"] ?? null,
            'fax'                      => $input["FAX"] ?? "",
            'birthday'                 => !empty($input["BIRTHDAY"]) ? date("Y-m-d", strtotime($input["BIRTHDAY"])) : null,
            'frequency'                => $input["FREQUENCY"] ?? null,
            'invoice_name_branch_bank' => $input["INVOICE_NAME_BRANCH_BANK"] ?? "",
            'bank_account_owner'       => $input["BANK_ACCOUNT_OWNER"] ?? "",
            'sale_position_id'         => $input["SALE_POSITION_ID"] ?? "",
            'sale_status'              => $input["SALE_STATUS"] ?? null,
            'order_view'               => $input["ORDER_VIEW"] ?? null,
            'created_at'               => !empty($input["CREATE_DATE"]) ? (DateTime::createFromFormat('d/m/Y H.i.s', $input["CREATE_DATE"])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($input["CREATE_DATE"]))) : null,
            'created_by'               => $input["CREATE_USER"] ?? "",
            'updated_at'               => !empty($input["UPDATE_DATE"]) ? (DateTime::createFromFormat('d/m/Y H.i.s', $input["UPDATE_DATE"])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($input["UPDATE_DATE"]))) : null,
            'updated_by'               => $input["UPDATE_USER"] ?? "",
        ];
        $allRole = Role::model()->pluck('id', 'code')->toArray();

        if ($isUpdate) {
            $dMSSyncCustomer            = $this->getFirstBy('code', $input["CUSTOMER_CODE"]);
            $dMSSyncCustomer->shop_id   = !empty($input["SHOP_ID"]) ? $input["SHOP_ID"] : $dMSSyncCustomer->shop_id;
            $dMSSyncCustomer->name      = !empty($input["CUSTOMER_NAME"]) ? $input["CUSTOMER_NAME"] : $dMSSyncCustomer->name;
            $dMSSyncCustomer->phone     = !empty($input["PHONE"]) ? $input["PHONE"] : $dMSSyncCustomer->phone;
            $dMSSyncCustomer->mobiphone = !empty($input["MOBIPHONE"]) ? $input["MOBIPHONE"] : $dMSSyncCustomer->mobiphone;
            $dMSSyncCustomer->email     = !empty($input["EMAIL"]) ? $input["EMAIL"] : $dMSSyncCustomer->email;
            $dMSSyncCustomer->status    = !empty($input["STATUS"]) ? $input["STATUS"] : $dMSSyncCustomer->status;
            $dMSSyncCustomer->birthday  = !empty($input["BIRTHDAY"]) ? date("Y-m-d", strtotime($input["BIRTHDAY"])) : $dMSSyncCustomer->birthday;

            if($dMSSyncCustomer->save()){
                if(!empty( $input["MOBIPHONE"]) ||  !empty($input["PHONE"])) $phone = $input["MOBIPHONE"] ?? $input["PHONE"];
                if (!empty($phone)) {
                    $phone = str_replace(" ", "", $phone);
                    $phone = preg_replace('/\D/', '', $phone);
                }
                $user = User::model()->where([
                    'code'       => $input['CUSTOMER_CODE'],
                    'company_id' => TM::getIDP()->company_id,
                    'store_id'   => TM::getIDP()->store_id,
                ])->first();
                if (empty($user)) {
                    throw new \Exception(Message::get("V003", "Code: #{$input['CUSTOMER_CODE']}"));
                }

                $user->phone     = !empty($phone) ? $phone : $user->phone;
                $user->code      = $input['CUSTOMER_CODE'];
                $user->name      = !empty($input['CUSTOMER_NAME']) ? $input['CUSTOMER_NAME'] : $user->name;
                $user->email     = array_get($input, 'EMAIL', $user->email);
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
            }

        } else {
            $dMSSyncCustomer = $this->create($param);
//            dd($dMSSyncCustomer->id);
            if ($dMSSyncCustomer) {
                $phone = $input["MOBIPHONE"] ?? $input["PHONE"];
                if (!empty($phone)) {
                    $phone = str_replace(" ", "", $phone);
                    $phone = preg_replace('/\D/', '', $phone);
                }

                $allRole = Role::model()->pluck('id', 'code')->toArray();

                $input['CUSTOMER_NAME'] = !empty($input['CUSTOMER_NAME']) ? $input['CUSTOMER_NAME'] : $input['SHORT_CODE'];

                $userGroup = UserGroup::model()->where([
                    'code'       => $type,
                    'company_id' => TM::getIDP()->company_id,
                ])->first();
                $paramUser = [
                    'user_customer_id' => $dMSSyncCustomer->id,
                    'phone'            => $phone,
                    'code'             => $input['CUSTOMER_CODE'],
                    'name'             => !empty($input['CUSTOMER_NAME']) ? $input['CUSTOMER_NAME'] : null,
                    'email'            => array_get($input, 'EMAIL') ?? null,
                    'role_id'          => $allRole[USER_ROLE_GUEST],
                    'type'             => USER_TYPE_CUSTOMER,
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
                $user      = new UserModel();
                $user->create($paramUser);
            }
        }
        $user = User::where('user_customer_id', $dMSSyncCustomer->id)->first();
        //Profile
        $profile = Profile::where(['user_id' => $user->id])->first();
        if (!empty($input['CUSTOMER_NAME'])) {
            $names = explode(" ", trim($input['CUSTOMER_NAME']));
            $first = $names[0];
            unset($names[0]);
            $last = !empty($names) ? implode(" ", $names) : null;
        }

        if (!empty($profile)) {
            $profile->user_id        = $user->id;
            $profile->email          = array_get($input, 'EMAIL', $profile->email);
            $profile->first_name     = !empty($first) ? $first : $profile->first_name;
            $profile->last_name      = !empty($last) ? $last : $profile->last_name;
            $profile->short_name     = !empty($input['CUSTOMER_NAME']) ? $input['CUSTOMER_NAME'] : $profile->short_name;
            $profile->full_name      = !empty($input['CUSTOMER_NAME']) ? $input['CUSTOMER_NAME'] : $profile->full_name;
            $profile->address        = array_get($input, 'ADDRESS', $profile->address);
            $profile->phone          = !empty($phone) ? $phone : $profile->phone;
            $profile->gender         = "O";
            $profile->lat            = !empty($input['LAT']) ? $input['LAT'] : $profile->lat;
            $profile->long           = !empty($input['LNG']) ? $input['LNG'] : $profile->long;
            $profile->avatar         = $avatar[1] ?? $profile->avatar;
            $profile->city_code      = array_get($input, 'Province', null);
            $profile->district_code  = array_get($input, 'District', null);
            $profile->ward_code      = array_get($input, 'Ward', null);
            $profile->id_number      = array_get($input, 'IDNO', null);
            $profile->is_active      = 1;
            $profile->indentity_card = array_get($input, 'IDNO', null);
            $profile->updated_by     = TM::getIDP()->sync_name;
            $profile->save();
        } else {
            $paramProfile = [
                'user_id'           => $user->id,
                'email'             => array_get($input, 'Email') ?? null,
                'first_name'        => $first,
                'last_name'         => $last,
                'short_name'        => $input['CUSTOMER_NAME'],
                'full_name'         => $input['CUSTOMER_NAME'],
                'address'           => array_get($input, 'ADDRESS', null),
                'phone'             => $phone,
                'gender'            => "O",
                'lat'               => !empty($input['LAT']) ? $input['LAT'] : null,
                'long'              => !empty($input['LNG']) ? $input['LNG'] : null,
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
        if (!empty($userGroup->code) && $userGroup->code === USER_GROUP_DISTRIBUTOR) {
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

        return $dMSSyncCustomer;
    }

    public function delete($code)
    {
        $userCustomer = $this->getFirstBy('code', $code);
        if ($userCustomer) {
            $userCustomer->status     = 0;
            $userCustomer->deleted    = 1;
            $userCustomer->deleted_at = date('Y-m-d H:i:s', time());
            $userCustomer->deleted_by = TM::getCurrentUserId() ?? null;
            if ($userCustomer->save()) {
                $user             = User::where('code', ($code))->first();
                $user->is_active  = 0;
                $user->deleted    = 1;
                $user->deleted_at = date('Y-m-d H:i:s', time());
                $user->deleted_by = TM::getCurrentUserId() ?? null;
                if ($user->save()) {
                    $profileUser             = Profile::where('user_id', $user->id)->first();
                    $profileUser->is_active  = 0;
                    $profileUser->deleted    = 1;
                    $profileUser->deleted_at = date('Y-m-d H:i:s', time());
                    $profileUser->deleted_by = TM::getCurrentUserId() ?? null;
                    if ($profileUser->save()) return $userCustomer;
                    else return false;
                }
            } else return false;
        } else return false;
    }
}
<?php
/**
 * User: kpistech2
 * Date: 2019-11-03
 * Time: 15:16
 */

namespace App\V1\Models;


use App\City;
use App\Company;
use App\District;
use App\Role;
use App\Store;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\UserStore;
use App\Ward;
use App\Warehouse;

class StoreModel extends AbstractModel
{
    public function __construct(Store $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            $this->checkUnique(['code' => $input['code'],], $id);

            $warehouse = null;
            if (!empty($input['warehouse_id'])) {
                $warehouse = Warehouse::model()->where('id', $input['warehouse_id'])->first();
            }

            $city = City::model()->where('code', $input['city_code'])->first();
            $district = District::model()->where('code', $input['district_code'])->first();
            $ward = null;
            if (!empty($input['ward_code'])) {
                $ward = Ward::model()->where('code', $input['ward_code'])->first();
            }

            if ($id) {
                $store = Store::find($id);
                if (empty($store)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $store->name = array_get($input, 'name', $store->name);
                $store->code = array_get($input, 'code', $store->code);
                $store->lat = array_get($input, 'lat', $store->lat);
                $store->long = array_get($input, 'long', $store->long);
                $store->address = $input['address'];
                $store->contact_phone = $input['contact_phone'];
                $store->long = array_get($input, 'long', $store->long);
                $store->description = array_get($input, 'description', null);
                $store->company_id = !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId();
                $store->email = !empty($input['email']) ? $input['email'] : null;
                $store->email_notify = !empty($input['email_notify']) ? $input['email_notify'] : null;
                $store->warehouse_id = $warehouse->id ?? $store->warehouse_id;
                $store->warehouse_code = $warehouse->code ?? $store->warehouse_code;
                $store->warehouse_name = $warehouse->name ?? $store->warehouse_name;

                $store->city_code = $city->code;
                $store->city_type = $city->type;
                $store->city_name = $city->name;

                $store->district_code = $district->code;
                $store->district_type = $district->type;
                $store->district_name = $district->name;

                if ($ward) {
                    $store->ward_code = $ward->code;
                    $store->ward_type = $ward->type;
                    $store->ward_name = $ward->name;
                }

                $store->updated_at = date("Y-m-d H:i:s", time());
                $store->updated_by = TM::getCurrentUserId();
                $store->save();
            } else {
                $param = [
                    'code'           => $input['code'],
                    'name'           => $input['name'],
                    'lat'            => $input['lat'],
                    'long'           => $input['long'],
                    'email'          => !empty($input['email']) ? $input['email'] : null,
                    'email_notify'   => !empty($input['email_notify']) ? $input['email_notify'] : null,
                    'warehouse_id'   => $warehouse->id ?? null,
                    'warehouse_code' => $warehouse->code ?? null,
                    'warehouse_name' => $warehouse->name ?? null,
                    'contact_phone'  => $input['contact_phone'] ?? 0,
                    'address'        => $input['address'] ?? null,
                    'city_code'      => $city->code,
                    'city_type'      => $city->type,
                    'city_name'      => $city->name,
                    'district_code'  => $district->code,
                    'district_type'  => $district->type,
                    'district_name'  => $district->name,
                    'description'    => array_get($input, 'description'),
                    'token'          => hash('sha256', $input['code'] . "|" . time()),
                    'company_id'     => !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId(),
                    'is_active'      => 1,
                ];

                if ($ward) {
                    $param['ward_code'] = $ward->code;
                    $param['ward_type'] = $ward->type;
                    $param['ward_name'] = $ward->name;
                }

                $store = $this->create($param);


                if (!empty($store->id)) {
                    $code =  $input['code'];
                    $checkCodeWarehouse = Warehouse::model()->where([
                        'company_id' =>  !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId(),
                        'store_id' => $store->id,
                        'code' => $code,
                    ])->first();
                    if (!empty($checkCodeWarehouse)) {
                        throw new \Exception(Message::get("V007", "Code Warehouse: #$code"));
                    }
                    $paramWarehouse = [
                        'code'        => $input['code'],
                        'name'        => $input['name'] ?? null,
                        'address'     => $input['address'] ?? null,
                        'discription' => $input['discription'] ?? null,
                        'store_id'    => $store->id,
                        'company_id'  => !empty($input['company_id']) ? $input['company_id'] : TM::getCurrentCompanyId(),
                    ];
                    $warehouseModel = new WarehouseModel();
                    $warehouseModel->create($paramWarehouse);

                    // Update stores
                    $store->warehouse_id = $warehouseModel->id ?? $store->warehouse_id;
                    $store->warehouse_code = $warehouseModel->code ?? $store->warehouse_code;
                    $store->warehouse_name = $warehouseModel->name ?? $store->warehouse_name;
                    $store->save();
                }

                // Assign for current User
                $role = Role::model()->where('code', USER_ROLE_ADMIN)->first();
                $company = Company::model()->where('id', $store->company_id)->first();
                if ($role && $company) {
                    $user = TM::info();
                    $now = date('Y-m-d H:i:s', time());
                    $user_store = new UserStore();
                    $user_store->user_id = TM::getCurrentUserId();
                    $user_store->user_code = $user['code'];
                    $user_store->user_name = $user['full_name'] ?? $user['email'];
                    $user_store->company_id = $company->id;
                    $user_store->company_code = $company->code;
                    $user_store->company_name = $company->name;
                    $user_store->role_id = $role->id;
                    $user_store->role_code = $role->code;
                    $user_store->role_name = $role->name;
                    $user_store->store_id = $store->id;
                    $user_store->store_code = $store->code;
                    $user_store->store_name = $store->name;
                    $user_store->is_active = 1;
                    $user_store->created_at = $now;
                    $user_store->created_by = TM::getCurrentUserId();
                    $user_store->updated_at = $now;
                    $user_store->updated_by = TM::getCurrentUserId();
                    $user_store->save();
                }
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }

        return $store;
    }

    private function getAutoToken()
    {
        $token = substr(str_shuffle('0123456789'), 1, 10);
        $checkUniqueAutoToken = Store::model()
            ->select('token')->where('token', 'like', $token)->first();
        if ($checkUniqueAutoToken) {
            $token = $this->getAutoToken();
        }
        return $token;
    }
}
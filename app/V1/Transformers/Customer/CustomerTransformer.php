<?php
/**
 * User: Administrator
 * Date: 21/12/2018
 * Time: 07:56 PM
 */

namespace App\V1\Transformers\Customer;


use App\Customer;
use App\Supports\TM_Error;
use App\V1\Models\CustomerModel;
use League\Fractal\TransformerAbstract;

class CustomerTransformer extends TransformerAbstract
{
    public function transform(Customer $customer)
    {
        try {

            // Get Point for Customer
            $customerModel = new CustomerModel();
            $customerPoint = $customerModel->getPoint($customer->id, $customer->group_id);
            $avatar = !empty($customer->profile->avatar) ? url('/v0') . "/img/" . $customer->profile->avatar : null;
            $address = object_get($customer, "profile.address", null);
            $type = $customerModel->getType($customerPoint);
            return [
                'id'        => $customer->id,
                'code'      => $customer->code,
                'name'      => $customer->name,
                'card_name' => $customer->card_name,
                'phone'     => $customer->phone,
                'email'     => $customer->email,
                'is_agency' => $customer->is_agency,
                'type'      => $type['name'],

                'group_id'   => $customer->group_id,
                'group_code' => object_get($customer, 'group.code'),
                'group_name' => object_get($customer, 'group.name'),

                'type_id'   => $customer->type_id,
                'type_code' => $type['code'],
                'type_name' => $type['name'],

                'point'      => $customerPoint,
                'used_point' => $customer->used_point,

                'first_name'      => object_get($customer, "profile.first_name", null),
                'last_name'       => object_get($customer, "profile.last_name", null),
                'short_name'      => object_get($customer, "profile.short_name", null),
                'full_name'       => object_get($customer, "profile.full_name", null),
                'branch_name'     => object_get($customer, "profile.branch_name", null),
                'address'         => $address,
                'receipt_address' => object_get($customer, "profile.receipt_address", $address),
                'birthday'        => object_get($customer, 'profile.birthday', null),
                'gender'           => object_get($customer, "profile.gender", "O"),
                'tax_number'      => object_get($customer, "profile.tax_number"),
                'account_number'  => object_get($customer, "profile.account_number"),
                'bank_type'       => object_get($customer, "profile.bank_type"),
                'spokesman'       => object_get($customer, "profile.spokesman"),
                'id_number'       => object_get($customer, "profile.id_number"),
                'gender_name'      => config('constants.STATUS.GENDER')
                [strtoupper(object_get($customer, "profile.gender", 'O'))],
                'avatar'          => $avatar,
                'is_active'       => $customer->is_active,
                'created_at'      => date('d-m-Y', strtotime($customer->created_at)),
                'updated_at'      => date('d-m-Y', strtotime($customer->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
